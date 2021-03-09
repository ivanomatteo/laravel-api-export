<?php

namespace IvanoMatteo\ApiExport;

use Illuminate\Support\Collection;

class PostmanFormat
{
    private $variables = [];

    public function create(Collection $routesInfo, $name = 'laravel_collection', $baseUri = null)
    {
        $data = $this->basePostmanData($name, $baseUri);
        $data['item'] = $this->createPostmanItems($routesInfo);

        return $data;
    }

    public function setVarable($name, $value)
    {
        $this->variables[$name] = $value;
    }

    public function basePostmanData($name, $baseUri = null)
    {
        $vars = [
            "base_url" => $baseUri ?? config('app.url'),
        ];

        $vars = array_merge($vars, $this->variables);
        $vars = $this->assocToPostmanFormat($vars);

        return [
            "variable" => $vars,
            "info" => [
                "name" => now()->format('YmdHis') . "_$name.json",
                "schema" => "https://schema.getpostman.com/json/collection/v2.1.0/collection.json",
            ],
            "item" => [],
        ];
    }

    public function createPostmanItems($routesInfo)
    {
        $postmanItems = [];
        foreach ($routesInfo as $rinfo) {
            foreach ($rinfo['routeMethods'] as $method) {
                if ($method !== 'HEAD') {
                    $postmanItems[] = $this->postmanItem($rinfo, $method);
                }
            }
        }

        return $postmanItems;
    }

    public function postmanItem($routeInfo, $method)
    {
        $headers = $this->assocToPostmanFormat($routeInfo['headers'], true);

        $item = [
            "name" => $routeInfo['routeName'] . ' - ' . $routeInfo['routeUri'],
            "request" => [
                "method" => $method,
                "header" => $headers,
                "url" => [
                    "raw" => '{{base_url}}/' . $routeInfo['routeUri'],
                    "host" => '{{base_url}}/' . $routeInfo['routeUri'],
                ],
            ],
        ];

        if (! empty($routeInfo['payload'])) {
            if ($method === 'GET') {
                $query = '?' . http_build_query($routeInfo['payload'], '', '&', PHP_QUERY_RFC3986);
                $item['request']['url']['raw'] .= $query;
                $item['request']['url']['host'] .= $query;
            } else {
                $item['request']['body'] = [
                    "mode" => 'raw',
                    'raw' => json_encode($routeInfo['payload']),
                ];
            }
        }

        return $item;
    }

    public function assocToPostmanFormat($assoc, $notNull = false)
    {
        $coll = collect($assoc);
        if ($notNull) {
            $coll = $coll->whereNotNull();
        }

        return $coll->map(function ($value, $name) {
            return [
                "key" => $name,
                "value" => $value,
            ];
        })->toArray();
    }
}
