<?php

namespace IvanoMatteo\ApiExport\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use IvanoMatteo\ApiExport\Facades\ApiExport;
use IvanoMatteo\ApiExport\Facades\PostmanFormat;

class ApiExportCommand extends Command
{
    public $signature = 'api-export:postman {name?} {baseUri?}';

    public $description = 'export api in postman format';

    public function handle()
    {
        $name = $this->argument('name');
        $baseUri = $this->argument('baseUri');

        $name = $name ?? config('api-export.defaultName') ?? 'laravel_collection';
        $baseUri = $baseUri ?? config('api-export.defaultBaseUri');

        $data = PostmanFormat::create(ApiExport::getRoutesInfo(), $name, $baseUri);

        Storage::put('postman/' . $data['info']['name'], json_encode($data, JSON_PRETTY_PRINT));
        echo "created: \n";
        echo storage_path('app/postman/' . $data['info']['name']) . "\n";
    }
}
