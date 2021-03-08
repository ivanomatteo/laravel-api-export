<?php

namespace IvanoMatteo\ApiExport;

use Illuminate\Support\Facades\Facade;

/**
 * @see \IvanoMatteo\ApiExport\ApiExport
 */
class ApiExportFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-api-export';
    }
}
