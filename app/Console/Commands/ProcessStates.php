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
		$this->process_states();
    }

	public function get_sitecode($state)
	{
		$sitecode = substr($state->name,0,8);
        if (!$sitecode) {
            throw new \Exception('Site code unknown for state id ' . $state->id . ' with name ' . $site->name);
        }
		return $sitecode;
	}
	
	public function find_incident($state)
	{
		$sitecode = $this->get_sitecode($state);
		if($siteincident = Incident::where('type', "site")->where('name', $sitecode)->first())
		{
			print "Found SITE incident ID: " . $siteincident->id . "\n";
			return $siteincident;
		}
		if($deviceincident = Incident::where('type', "device")->where('name', $state->name)->first())
		{
			print "Found DEVICE incident ID: " . $deviceincident->id . "\n";
			return $deviceincident;
		}
		print "No incident found!\n";
		return null;
	}

	public function find_site_states($state)
	{
		$sitecode = $this->get_sitecode($state);
		$time1 = $state->created_at->subMinutes(10) . "\n";
		$time2 = $state->created_at->addMinutes(10) . "\n";
		//$sitestate = State::where('type','device')->whereBetween('created_at', array($time1, $time2))->get();
		$sitestates = State::where('name', 'like', '%' . $sitecode . '%')->where('type','device')->whereBetween('created_at', array($time1, $time2))->where('id','<>',$state->id)->get();
		if($sitestates->isEmpty())
		{
			print "No other site states found!\n";
			return null;
		}
		print "Found " . $sitestates->count() . " other site devices!\n";
		return $sitestates;
	}	
	
	public function create_site_incident($state)
	{
		print "Creating a SITE incident....";
		$sitecode = $this->get_sitecode($state);
		$name = $sitecode;
		$type = "site";
		$newinc = Incident::create([
			'name'		=>	$name,
			'type'		=>	$type,
		]);
		$state->incident_id = $newinc->id;
		$state->save();
		print "Created a " . $type . " incident " . $newinc->id . "\n";
	}
	
	public function create_device_incident($state)
	{
		$name = $state->name;
		$type = "device";
		$newinc = Incident::create([
			'name'		=>	$name,
			'type'		=>	$type,
		]);
		$state->incident_id = $newinc->id;
		$state->save();
		print "Created a " . $type . " incident " . $newinc->id . "\n";
	}

/*
	public function create_incident($state)
	{
		$sitecode = $this->get_sitecode($state);
		if ($sitestates = $this->find_site_states($state))  //If there are other STATES from the same site, create a SITE incident
		{
			print "Detected other states from site " . $sitecode . "...\n";
			$name = $sitecode;
			$type = "site";
		} else { // No other states from same site and device is down, create a single device incident.
			print "Detected no other states from site " . $sitecode . "...\n";
			$name = $state->name;
			$type = "device";
		}
		$newinc = Incident::create([
			'name'		=>	$name,
			'type'		=>	$type,
		]);
		$state->incident_id = $newinc->id;
		$state->save();
		print "Created a " . $type . " incident " . $newinc->id . "\n";
	}
/**/

	//Process any states that do not currently have an incident assigned.
	public function process_states()
	{
		print "Processing States...\n";
		// GET the state of DEVICES detected by event information
		$tenminsago = Carbon::now()->subMinutes(10);  //data 10 minutes ago.
		$states = State::where('incident_id', null)->where('type','device')->where('created_at','<', $tenminsago)->get();
		if($states->isEmpty()) {
            //throw new \Exception('ERROR: I didnt get any states from the database whatever that is');
			print "No STATES to process!\n";
        }
		// Loop through each site and do something?
		foreach($states as $state) {
			try {
                $this->processStateIncidents($state);
            } catch (\Exception $e) {
                //$this->log('Exception crap happened: '.$e->getMessage());
            }
        }
    }

	//Process incidents for given state
    public function processStateIncidents ($state)
    {
		print 'Processing State ID ' . $state->id . ' NAME ' . $state->name . "...\n";
		if($incident = $this->find_incident($state))
		{
			//Look for an existing site or device incident that matches this device, if so assign the incident to the state.
			print "Found existing " . $incident->type . " incident ID " . $incident->id . "\n";
			$state->incident_id = $incident->id;
			$state->save();
		} elseif ($sitestates = $this->find_site_states($state)){
			//If there are multiple devices from the same site, create a SITE INCIDENT
			$incident = $this->create_site_incident($state);
			$state->incident_id = $incident->id;
			$state->save();
		} elseif ($state->state == 0) {
			//If the device is still down, create a device incident.
			$incident = $this->create_device_incident($state);
			$state->incident_id = $incident->id;
			$state->save();
		} elseif ($state->state == 1 && $state->created_at < Carbon::now()->subMinutes(30)) {
			//If device is restored, not assigned to an incident, no incident found, and is 4 hours old, delete.
			print "Device state is online with no active incidents and is stale.  Deleting STATE!\n";
			$state->delete();
		} else {
			print "Device state is online with no active incidents.  Waiting a bit longer...\n";
		}
	}
}
