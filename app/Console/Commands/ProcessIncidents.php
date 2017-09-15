<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\State;
use App\Incident;

class ProcessIncidents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerter:process-incidents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process incidents and manage opening/updating tickets';

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
        //
    }
}
