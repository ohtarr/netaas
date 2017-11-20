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
		//$this->process_events();
		//$this->process_states();
		//$this->process_incidents();
		$this->processEvents();
		$this->processStates();
		$this->processIncidents();
    }
	
	public function test_mail()
	{
		Mail::to("andrew.jones@kiewit.com")->send(new TestMail());
	}
/*
	public function process_events()
	{
		print "\nProcessing EVENTS...\n";
		$events = Event::where('processed', 0)->where('type', "device")->get();
		$oldevents = Event::where('updated_at', '<', Carbon::now()->subDays(3))->get();

		if($events->isNotEmpty())
		{
			print $events->count() . " EVENTS TO PROCESS!\n";
			foreach($events as $event)
			{
				$event->process();
			}
		} else {
			print "NO EVENTS TO PROCESS!\n";
		}
		if ($oldevents->isNotEmpty()) {
			foreach($oldevents as $oldevent)
			{
				$oldevent->delete();
			}
		}
	}
/**/
/*
	public function process_states()
	{
		print "\nProcessing STATES...\n";
		// GET the state of DEVICES detected by event information
		//$states = State::where('type','device')->where('processed',0)->get();
		$states = State::where('type','device')->get();
		//$states = State::where('incident_id', null)->where('type','device')->where('created_at','<', $tenminsago)->get();
		if($states->isEmpty()) {
            //throw new \Exception('ERROR: I didnt get any states from the database whatever that is');
			print "No STATES to process!\n";
        } else {
			print $states->count() . " STATES to process!\n";
			// Loop through each site and do something?
			foreach($states as $state) {
				try {
					//$this->process_state($state);
					$state = $state->fresh();
					$state->process2();
				} catch (\Exception $e) {
					//$this->log('Exception crap happened: '.$e->getMessage());
				}
			}
		}
    }
/**/
/*
	public function process_incidents()
	{
		print "\nProcessing INCIDENTS...\n";
		// GET all incidents
		$incidents = Incident::all();
		if($incidents->isEmpty()) {
            //throw new \Exception('ERROR: I didnt get any states from the database whatever that is');
			print "No INCIDENTS to process!\n";
        } else {
			print $incidents->count() . " INCIDENTS to process!\n";
			// Loop through each site and do something?
			foreach($incidents as $incident) {
				try {
					$incident->process();
				} catch (\Exception $e) {
					//$this->log('Exception crap happened: '.$e->getMessage());
				}
			}
		}
	}
/**/
/*
	public function clear_states()
	{
		$states = State::all();
		foreach($states as $state)
		{
			$state->processed=0;
			$state->incident_id=null;
			$state->save();
		}
	}
/**/
/*
	public function clear_tickets()
	{
		$tickets = ServiceNowIncident::where("sys_created_by","=",env("SNOW_USERNAME"))->where("state","=", 1)->get();
		foreach($tickets as $ticket)
		{
			$ticket->close();
		}
	}
/**/	
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
		$states = State::whereNull("incident_id")->get();
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
