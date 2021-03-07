<?php

namespace IvanoMatteo\ApiExport;

use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Route as RouteItem;
use ReflectionMethod;
use ReflectionParameter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class PostmanFormat
{

    private $data;

    public function __construct($routesInfo, $name = 'laravel_collection')
    {
        $this->data = $this->basePostmanData($name);
        $this->data['item'] = $this->createPostmanItems($routesInfo);
    }

    public function toArray()
    {
        return $this->data;
    }

    function basePostmanData($name)
    {
        return [
            "variable" => [
                [
                    "key" => "base_url",
                    "value" => "http://localhost"
                ]
            ],
            "info" => [
                "name" => now()->format('YmdHis') . "_$name.json",
                "schema" => "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
            ],
            "item" => []
        ];
    }

    function createPostmanItems($routesInfo)
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

    function postmanItem($routeInfo, $method)
    {
        $item = [

            "name" => $routeInfo['routeName'] . ' - ' . $routeInfo['routeUri'],
            "request" => [
                "method" => $method,
                "header" => $routeInfo['headers'],
                "url" => [
                    "raw" => '{{base_url}}/' . $routeInfo['routeUri'],
                    "host" => '{{base_url}}/' . $routeInfo['routeUri'],
                ]
            ]
        ];

        if (!empty($routeInfo['parameters'])) {
            $item['request']['body'] = [
                "mode" => 'raw',
                'raw' => json_encode($routeInfo['parameters'])
            ];
        }

        return $item;
    }
}
