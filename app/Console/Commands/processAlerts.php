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
		//$this->clear_states();
		//$this->clear_tickets();
		$this->process_incidents();
		$this->process_events();
		$this->process_states();
    }
	
	public function test_mail()
	{
		Mail::to("andrew.jones@kiewit.com")->send(new TestMail());
	}

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
	
	public function process_states()
	{
		print "\nProcessing STATES...\n";
		// GET the state of DEVICES detected by event information
		$states = State::where('type','device')->where('processed',0)->get();
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
					$state->process();
				} catch (\Exception $e) {
					//$this->log('Exception crap happened: '.$e->getMessage());
				}
			}
		}
    }

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
	
	public function process_tickets()
	{
		print "Processing TICKETS...\n";
		//$incidents = Incident::all();
		$incidents = Incident::whereNotNull("ticket")->get();

		// GET all tickets
		//$tickets = Ticket::all();
		
		// Loop through each ticket and do something?
		if($incidents->isEmpty())
		{
			print "No TICKETS to process!\n";
		} else {
			print $incidents->count() . " TICKETS to process!\n";
			foreach($incidents as $incident) {
				try {
					//$ticket = Ticket::where('sysid', $incident->ticket)->withTrashed()->first();
					$ticket = $incident->get_ticket();
					if($ticket)
					{
						$ticket->process();
					}
				} catch (\Exception $e) {
					//$this->log('Exception crap happened: '.$e->getMessage());
				}
			}
		}
	}

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
	
	public function clear_tickets()
	{
		$tickets = ServiceNowIncident::where("sys_created_by","=","netman.ldapint")->where("state","=", 1)->get();
		foreach($tickets as $ticket)
		{
			$ticket->close();
		}
	}

}
