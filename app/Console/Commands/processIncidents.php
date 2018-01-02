<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Incident;

class processIncidents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netaas:processIncidents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all Incidents';

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
		$this->process();
    }

	public static function process()
	{
		print "*******************************************\n";
		print "***********Processing Incidents************\n";
		print "*******************************************\n";
		$incidents = Incident::all();
		if($incidents->isNotEmpty())
		{
			foreach($incidents as $incident)
			{
				print "Processing INCIDENT " . $incident->name . "\n";
				$incident->process();
			}
		}
	}
}
