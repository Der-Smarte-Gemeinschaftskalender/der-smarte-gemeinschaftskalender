<?php

namespace App\Console\Commands;

use App\Jobs\FetchImportedEvents;
use Illuminate\Console\Command;


class FetchImportedEventsCommand extends Command
{
    protected $signature = 'app:fetch-imported-events';
    protected $description = 'Get events from URL and import them';

    public function handle(): void
    {
        FetchImportedEvents::dispatchSync();
    }
}
