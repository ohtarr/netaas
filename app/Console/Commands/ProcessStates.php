<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\State;
use App\Incident;
use Carbon\Carbon;

class ProcessStates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerter:process-states';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process device states and populate the incidents table';

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
		$this->processStates();
    }
	
	//Process any states that do not currently have an incident assigned.
	public function processStates()
	{
		print "Processing States...\n";
		// GET the state of DEVICES detected by event information
		$states = State::where('type','device')->where('processed',0)->where('updated_at', '<', Carbon::now()->subMinutes(0))->get();
		//$states = State::where('incident_id', null)->where('type','device')->where('created_at','<', $tenminsago)->get();
		if($states->isEmpty()) {
            //throw new \Exception('ERROR: I didnt get any states from the database whatever that is');
			print "No STATES to process!\n";
        }
		// Loop through each site and do something?
		foreach($states as $state) {
			try {
                $this->process_state($state);
            } catch (\Exception $e) {
                //$this->log('Exception crap happened: '.$e->getMessage());
            }
        }
    }

	public function process_state($state)
	{
		//process state incident, finding or creating an incident if needed.
		$incident = $state->process_state_incident();
		$sitestates = $state->find_site_states();
		print $sitestates->count() . "\n";
		print $this->get_site_resolved_states($state->get_sitecode())->count() . "\n";
		if($sitestates->count() == $this->get_site_resolved_states($state->get_sitecode())->count())
		{
			$incident->resolved = 1;
			$incident->save();
		}
		$state->processed = 1;
		$state->save();
	}
	
	public function get_site_resolved_states($sitecode)
	{
		return State::where('name', 'like', "%".$sitecode."%")->where('resolved', 1)->get();
	}

/*
	//Process incidents for given state
    public function processState ($state)
    {
		print 'Processing State ID ' . $state->id . ' NAME ' . $state->name . "...\n";
		if($state->incident_id == 0)
		{
			//Look for an existing site or device incident that matches this device, if so assign the incident to the state.
			if($incident = $this->find_incident($state))
			{
				print "Found existing " . $incident->type . " incident ID " . $incident->id . "\n";
				$state->incident_id = $incident->id;
				//comment_ticket();
				$state->save();
			//If there are multiple devices from the same site, create a SITE INCIDENT
			} elseif ($this->find_site_states($state)->count() > 1){
				$incident = $this->create_site_incident($state);
				$state->incident_id = $incident->id;
				$state->save();
				//comment_ticket();
			} elseif ($state->resolved == 0) {
				//If the device is still down, create a device incident.
				$incident = $this->create_device_incident($state);
				$state->incident_id = $incident->id;
				$state->save();
				//comment_ticket();
			} elseif ($state->resolved == 1 && $state->created_at < Carbon::now()->subMinutes(5)) {
				//If device is restored, not assigned to an incident, no incident found, and is 4 hours old, delete.
				print "Device state is online with no active incidents and is stale.  Deleting STATE!\n";
				$state->delete();
			} else {
				print "Device state is online with no active incidents.  Waiting a bit longer...\n";
			}
		} else {
			//$this->comment_ticket($state);
			//find_site_states()
		}
		$state->processed = 1;
		$state->save;
	}
	/**/
}
