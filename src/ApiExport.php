<?php

namespace IvanoMatteo\ApiExport;

use Illuminate\Http\Request;
use Illuminate\Routing\Route as RouteItem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionParameter;
use Throwable;

class ApiExport
{
    private $globalHeaders = [];
    private $payloadByRouteName = [];
    private $middlewareHeaders = [];
    private $middlewareGroupHeaders = [
        'api' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ],
    ];
    private $customAdjustments = [];

    private $middlewareGroups;
    private $routeMiddelwares;

    public function __construct()
    {
        $kernel = resolve("\App\Http\Kernel");
        $this->middlewareGroups = $kernel->getMiddlewareGroups();
        $this->routeMiddelwares = $kernel->getRouteMiddleware();
    }

    public function setPayloadByRouteName(string $routeName, ?callable $payloadGenerator)
    {
        if (empty($payloadGenerator)) {
            unset($this->payloadByRouteName[$routeName]);
        } else {
            $this->payloadByRouteName[$routeName] = $payloadGenerator;
        }
    }

    public function setGlobalHeader(string $name, ?string $value)
    {
        if (empty(trim($value))) {
            unset($this->globalHeaders[$name]);
        } else {
            $this->globalHeaders[$name] = $value;
        }
    }

    public function setMiddlewareHeaders(string $middlewareName, ?array $headers)
    {
        $fullname = $this->resolveMiddlewareFullName($middlewareName);

        if (empty($headers)) {
            unset($this->middlewareHeaders[$fullname]);
        } else {
            $this->middlewareHeaders[$fullname] = $headers;
        }
    }

    public function setMiddlewareGroupHeaders(string $middlewareGroup, ?array $headers)
    {
        if (empty($headers)) {
            unset($this->middlewareGroupHeaders[$middlewareGroup]);
        } else {
            $this->middlewareGroupHeaders[$middlewareGroup] = $headers;
        }
    }

    public function resolveMiddlewareFullName(string $name)
    {
        $parts = explode(':', $name);

        $middlewareName = $parts[0];
        array_shift($parts);
        $args = implode(':', $parts);

        if ($this->routeMiddelwares[$middlewareName] ?? null) {
            $middlewareName = $this->routeMiddelwares[$middlewareName];
        }
        if ($args) {
            $middlewareName .= ':' . $args;
        }

        return $middlewareName;
    }

    public function setCustomAdjustments(string $name, ?callable $adjust)
    {
        if (empty($adjust)) {
            unset($this->customAdjustments[$name]);
        } else {
            $this->customAdjustments[$name] = $adjust;
        }
    }

    private function wildcardRuleMatch($rule, $subject)
    {
        $regex = str_replace("\\*", '.*', str_replace("/", "\\/", preg_quote($rule)));

        return preg_match('/^' . $regex . '$/', $subject);
    }

    private function filterRoutes(RouteItem $r)
    {
        $name = $r->getName();
        if (config('api-export.onlyNamed')) {
            if (empty($name)) {
                return false;
            }
        }
        foreach (config('api-export.nameDenyRules') as $rule) {
            if ($this->wildcardRuleMatch($rule, $name)) {
                return false;
            }
        }

        $allowRules = config('api-export.nameAllowRules');
        if (!empty($allowRules)) {
            foreach ($allowRules as $rule) {
                if ($this->wildcardRuleMatch($rule, $name)) {
                    return true;
                }
            }

            return false;
        }

        $uri = $r->uri();

        foreach (config('api-export.uriDenyRules') as $rule) {
            if ($this->wildcardRuleMatch($rule, $uri)) {
                return false;
            }
        }

        $allowRules = config('api-export.uriAllowRules');
        if (!empty($allowRules)) {
            foreach ($allowRules as $rule) {
                if ($this->wildcardRuleMatch($rule, $uri)) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    public function getRoutesInfo()
    {
        $namedRoutes = collect(Route::getRoutes())
            ->filter(function ($r) {
                return $this->filterRoutes($r);
            });

        return $namedRoutes->map(function (RouteItem $r) {
            $routeName = $r->getName();
            $routeUri = $r->uri();

            $routeUri = Str::replaceArray('?', [' (opzionale)'], $routeUri);

            $routeMethods = $r->methods();
            $middlewareGroups = array_intersect_key(
                array_fill_keys($r->gatherMiddleware(), true),
                $this->middlewareGroups,
            );
            $middlewares = array_fill_keys(Route::gatherRouteMiddleware($r), true);
            $controller = null;

            try {
                $controller = $r->getController();
            } catch (Throwable $t) {
            }
            $method = $r->getActionMethod();

            $payload = null;
            $requestClass = null;

            if ($payloadGen = $this->payloadByRouteName[$routeName] ?? null) {
                $payload = $payloadGen();
            } elseif ($controller) {
                $requestClass = $this->getRequestClass($controller, $method);
                if ($requestClass) {
                    $payload = $this->getFakeParameters($requestClass);
                }
            }

            $headers = $this->globalHeaders;

            foreach ($middlewareGroups as  $g => $bool) {
                $h = $this->middlewareGroupHeaders[$g] ?? null;
                if ($h) {
                    $headers = array_merge($headers, $h);
                }
            }
            foreach ($middlewares as $m => $bool) {
                $h = $this->middlewareHeaders[$m] ?? null;
                if ($h) {
                    $headers = array_merge($headers, $h);
                }
            }

            $info = compact(
                'routeName',
                'routeUri',
                'routeMethods',
                'requestClass',
                'payload',
                'middlewares',
                'middlewareGroups',
                'headers'
            );

            foreach ($this->customAdjustments as $name => $payloadAdjust) {
                $info = $payloadAdjust($info);
            }

            return $info;
        });
    }

    public function getRequestClass($controller, $method)
    {
        if (get_class($controller) === $method && method_exists($controller, '__invoke')) {
            $method = '__invoke';
        }
        $methodInfo = new ReflectionMethod($controller, $method);
        $request = collect($methodInfo->getParameters())->first(function (ReflectionParameter $p) {
            $class = optional($p->getType())->getName();

            return $class && is_subclass_of($class, Request::class);
        });

        return optional(optional($request)->getType())->getName();
    }

    public function getFakeParameters($requestClass)
    {
        $r = (new $requestClass());
        if (method_exists($r, 'fake')) {
            return $r->fake();
        }
        if (method_exists($r, 'rules')) {
            return collect($r->rules())
                ->mapWithKeys(function ($rules, $field) {
                    return [$field => "<$field>"];
                })
                ->toArray();
        }
    }
}
