<?php

namespace IvanoMatteo\ApiExport;

use Illuminate\Support\Collection;

class PostmanFormat
{
    private $data;

    public function __construct(Collection $routesInfo, $name = 'laravel_collection')
    {
        $this->data = $this->basePostmanData($name);
        $this->data['item'] = $this->createPostmanItems($routesInfo);
    }

    public function toArray()
    {
        return $this->data;
    }

    public function basePostmanData($name)
    {
        return [
            "variable" => [
                [
                    "key" => "base_url",
                    "value" => config('app.url'),
                ],
            ],
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
        $headers = collect($routeInfo['headers'])->whereNotNull()->map(function ($value, $name) {
            return [
                "key" => $name,
                "value" => $value,
            ];
        })->toArray();

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
            $item['request']['body'] = [
                "mode" => 'raw',
                'raw' => json_encode($routeInfo['payload']),
            ];
        }

        return $item;
    }
}
