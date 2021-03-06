<?php

namespace IvanoMatteo\ApiExport;

use Illuminate\Support\Facades\App;
use IvanoMatteo\ApiExport\Commands\ApiExportCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ApiExportServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-api-export')
            ->hasConfigFile()
            //->hasViews()
            ->hasCommand(ApiExportCommand::class);

        App::singleton('laravel-api-export', function () {
            return new ApiExport();
        });
        App::singleton('laravel-api-export-postman', function () {
            return new PostmanFormat();
        });
    }
}
