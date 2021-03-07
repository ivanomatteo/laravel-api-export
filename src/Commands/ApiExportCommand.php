<?php

namespace IvanoMatteo\ApiExport\Commands;

use Illuminate\Console\Command;

class ApiExportCommand extends Command
{
    public $signature = 'laravel-api-export';

    public $description = 'My command';

    public function handle()
    {
        $this->comment('All done');
    }
}
