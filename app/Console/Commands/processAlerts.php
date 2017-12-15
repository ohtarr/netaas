<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Event;
use App\State;
use App\Incident;
use App\ServiceNowIncident;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;

class processAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netaas:process-alerts';

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
		$this->processEvents();
		$this->processStates();
		$this->processIncidents();
    }
	
	public function test_mail()
	{
		Mail::to("andrew.jones@kiewit.com")->send(new TestMail());
	}

	public function processEvents()
	{
		$events = Event::where("processed",0)->get();
		
		foreach($events as $event)
		{
			$state = $event->get_state();
			if($state)
			{
				$state->resolved = $event->resolved;
				$state->processed = 0;
				$state->save();
			} else {
				$state = $event->create_state();
			}
			if($state)
			{
				$event->processed = 1;
				$event->save();
			}
		}
	}
	
	public function processStates()
	{
		$states = State::whereNull("incident_id")->where("type","device")->get();
		if($states->isNotEmpty())
		{
			foreach($states as $state)
			{
				$state = $state->fresh();
				$incident = $state->find_incident();
				if($incident)
				{
					//Assign incident_id
					$state->incident_id = $incident->id;
					$state->save();
				} else {
					//Create Incident
					if($state->updated_at < Carbon::now()->subMinutes(env('TIMER_STATE_SAMPLING_DELAY')) && $state->resolved == 0)
					{
						$state->create_new_incident();
					} elseif ($state->updated_at < Carbon::now()->subMinutes(env('TIMER_DELETE_STALE_STATES')))
					{
						$state->delete();
					}
				}
			}
		}
	}
	
	public function processIncidents()
	{
		$incidents = Incident::all();
		if($incidents->isNotEmpty())
		{
			foreach($incidents as $incident)
			{
				$incident->process();
			}
		}
	}
}
