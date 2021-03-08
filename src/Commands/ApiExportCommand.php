<?php

namespace IvanoMatteo\ApiExport\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use IvanoMatteo\ApiExport\Facades\ApiExport;
use IvanoMatteo\ApiExport\Facades\PostmanFormat;

class ApiExportCommand extends Command
{
    public $signature = 'api-export:postman';

    public $description = 'export api in postman format';

    public function handle()
    {
        $data = PostmanFormat::create(ApiExport::getRoutesInfo());

        Storage::put('postman/' . $data['info']['name'], json_encode($data, JSON_PRETTY_PRINT));
        echo "created: \n";
        echo storage_path('app/postman/' . $data['info']['name']) . "\n";
    }
}
