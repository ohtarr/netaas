<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Event;
use App\State;
use App\Incident;
use App\ServiceNowIncident;
use Carbon\Carbon;
use App\Console\Commands\processEvents;
use App\Console\Commands\processStates;
use App\Console\Commands\processIncidents;

class processAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netaas:processAlerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all alerts';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
		processEvents::process();
		processStates::process();
		processIncidents::process();
    }

}
