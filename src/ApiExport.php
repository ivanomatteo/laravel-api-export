<?php

namespace IvanoMatteo\ApiExport;

use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Route as RouteItem;
use ReflectionMethod;
use ReflectionParameter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class ApiExport
{
    private $globalHeaders = [];

    private $routeSignaturePayloads = [];
    private $routeNamePayloads = [];

    function getRoutesInfo()
    {
        $namedRoutes = collect(Route::getRoutes())->filter(function ($r) {
            return !empty($r->getName());
        });

        return $namedRoutes->map(function (RouteItem $r) {
            $routeName = $r->getName();
            $routeUri = $r->uri();

            $routeUri = Str::replaceArray('?', [' (opzionale)'], $routeUri);

            $routeMethods = $r->methods();
            $middlewares = array_fill_keys($r->gatherMiddleware(), true);


            $controller = $r->getController();
            $method = $r->getActionMethod();

            $parameters = null;

            $requestClass = $this->getRequestParameter($controller, $method);
            if ($requestClass) {
                $parameters = $this->getFakeParameters($requestClass);
            }

            $headers = [
                [
                    "key" => "Accept",
                    "value" => "application/json"
                ],
                [
                    "key" => "Content-Type",
                    "value" => "application/json"
                ]
            ];

            /*  $useBearerToken = !empty($routeInfo['middlewares']['auth:sanctum']) ||
            !empty($routeInfo['middlewares']['auth:api']);

        if($useBearerToken){
            $headers[] =  [
                "key" => "Authorization",
                "value" => "Bearer <token>"
            ];
        } */

            return compact(
                'routeName',
                'routeUri',
                'routeMethods',
                'requestClass',
                'parameters',
                'middlewares',
                'headers'
            );
        });
    }


    function getRequestParameter($controller, $method)
    {
        $methodInfo = new ReflectionMethod($controller, $method);
        $request = collect($methodInfo->getParameters())->first(function (ReflectionParameter $p) {
            $class = optional($p->getType())->getName();
            return $class && is_subclass_of($class, Request::class);
        });
        return optional(optional($request)->getType())->getName();
    }

    function getFakeParameters($requestClass)
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
