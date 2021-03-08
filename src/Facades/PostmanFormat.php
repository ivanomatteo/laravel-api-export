<?php

namespace IvanoMatteo\ApiExport\Facades;

use Illuminate\Support\Facades\Facade;


class PostmanFormat extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-api-export-postman';
    }
}
