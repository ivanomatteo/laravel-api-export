<?php

namespace IvanoMatteo\ApiExport\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \IvanoMatteo\ApiExport\ApiExport
 */
class ApiExport extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-api-export';
    }
}
