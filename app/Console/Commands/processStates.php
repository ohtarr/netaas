<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\State;
use App\Incident;
use App\IncidentType;
use Carbon\Carbon;

class processStates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netaas:processStates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all unassigned states.';

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
		print "*******************************************\n";
		print "***********Processing States***************\n";
		print "*******************************************\n";
		$this->processExistingDevice();
		$this->processExistingSite();
		$this->processExistingCompany();
		$this->processNewCompany();
		$this->processNewSite();
		//$this->processNewDeviceNetwork();
		//$this->processNewDeviceServer();
		$this->processNewDevice();
		$this->processStale();
	}

	//Look for existing DEVICE incident, if exists assign states to it.
	public static function processExistingDevice()
	{
		print "Processing Existing Devices\n";
		//Get all states that are not already assigned to an incident and foreach through them.
		$states = State::whereNull("incident_id")->get();
		foreach($states as $state)
		{
			$state->refresh();  //Get a fresh copy from the database just in case a previous rule modified it.
			//Search for an incident that has the name of this states device_name.
			$deviceincident = Incident::where('name',$state->device_name)->first();
			//If we find an existing incident that matches this device_name, find all states for this device.
			//and assign them to this incident.
			if($deviceincident)
			{
				$devicestates = State::whereNull("incident_id")->where('device_name',$state->device_name)->get();
				foreach($devicestates as $devicestate)
				{
					print "Found existing Incident for state " . $devicestate->device_name . ".  Adding it to existing incident\n";
					$devicestate->incident_id = $deviceincident->id;
					$devicestate->save();
				}
			}
		}
	}

	//look for an existing network SITE incident.  If one exists, assign all related states to it.
	public static function processExistingSite()
	{
		print "Processing Existing Sites\n";
		//Find all unassigned states and Check if an existing site incident exists for it.
		$states = State::whereNull("incident_id")->get();
		foreach($states as $state)
		{
			$state->refresh();
			$siteincident = Incident::where('name', $state->get_sitecode())->first();

			if($siteincident)
			{
				//If a site incident exists already, find all states that match site name and assign them to the incident.
				$sitestates = $state->getUnassignedUniqueDeviceSiteStates();
				foreach($sitestates as $sitestate)
				{
					foreach($sitestate as $sitedevicestate)
					{
						print "Found existing incident for " . $sitedevicestate->device_name . ".  Adding to existing incident.\n";
						$sitedevicestate->incident_id = $siteincident->id;
						$sitedevicestate->save();
					}
				}
			}
		}
	}

	//Look for existing COMPANY outage, if exists, assign ALL states to it.
	public static function processExistingCompany()
	{
		print "Processing Existing Company Outage\n";
		//Find the incidentType for COMPANY_CRITICAL.  If it doesn't exist, exit function.
		$inctype = IncidentType::where("name","COMPANY_CRITICAL")->first();
		if(!$inctype)
		{
			return;
		}
		//Find any existing COMPANY_CRITICAL incidents.  If it exists, assign all unassigned states to it.
		$companyincident = Incident::where('type_id',$inctype->id)->first();
		if($companyincident)
		{
			$states = State::whereNull("incident_id")->get();
			foreach($states as $state)
			{
				print "Adding state " . $state->device_name . " to company outage!!\n";
				$state->incident_id = $companyincident->id;
				$state->save();
			}
			return $companyincident;
		}
	}

	//Check if a new COMPANY incident needs to be created.
	public static function processNewCompany()
	{
		print "Processing New Company Outage\n";
		//Look for COMPANY_CRITICAL incidentType.  If it doesn't exist, exit the function.
		$inctype = IncidentType::where("name","COMPANY_CRITICAL")->first();
		$states = State::whereNull("incident_id")->where('updated_at',"<",Carbon::now()->subMinutes(env('TIMER_STATE_SAMPLING_DELAY')))->get();
		if($states->isEmpty())
		{
			return;
		}
		//Get all unassigned states.
		$states = State::whereNull("incident_id")->get();
		$sites = [];  
		$devices = $states->groupBy('device_name');
		foreach($devices as $device)
		{
			foreach($device as $entity)
			{
				$sites[] = substr($entity->device_name,0,8);  //List of SITES for each state.  Used to determine number of SITES currently in alarm state.
			}
		}
		$sites = array_unique($sites);  //Remove duplicates to get an accurate site count.
		//If the count of unassigned sites in alert state is larger than the configured COMPANY_OUTAGE_COUNT, create a company outage incident
		if(count($sites) < env("COMPANY_OUTAGE_COUNT"))
		{
			return;
		}
		print "Detected more than " . env("COMPANY_OUTAGE_COUNT") . " alert states.  Creating a new COMPANY OUTAGE\n";
		$newinc = Incident::create([
			'name'		=>	"company",
			'type_id'	=>	$inctype->id,
		]);
		//Assign ALL unassigned states to this company outage incident.
		foreach($states as $state)
		{
			print "Adding state " . $state->device_name . " to company outage incident\n";
			$state->incident_id = $newinc->id;
			$state->processed = 1;
			$state->save();
		}
		return $newinc;
	}

	//Check if a new SITE incident needs to be created.
	public static function processNewSite()
	{
		print "Processing New Sites\n";
		//Get all states that are unassigned and older than TIMER_STATE_SAMPLING_DELAY
		$states = State::whereNull("incident_id")->where('updated_at',"<",Carbon::now()->subMinutes(env('TIMER_STATE_SAMPLING_DELAY')))->get();
		foreach($states as $state)
		{
			$state->refresh();
			//Get ServiceNowLocation for this state.
			$location = $state->get_location();
			//assume MEDIUM priority.
			$inctype = IncidentType::where("name","SITE_MEDIUM")->first();
			//If we have a valid location, get the priority of the site to determine incident priority.
			//Overwrite inctype to SITE_HIGH if needed.
			if($location)
			{
				if($location->u_priority == 2)
				{
					$inctype = IncidentType::where("name","SITE_HIGH")->first();
				}
			}
			//If we can't find the proper IncidentType, exit the function.
			if(!$inctype)
			{
				return;
			}
			//Get all unassigned states that match this states sitecode.
			$siteStates = $state->getUnassignedUniqueDeviceSiteStates();

			//If there is more than 1 state that match sitecode, create a SITE incident.
			if($siteStates->count() > 1)
			{
				print "Detected more than 1 alert state from site " . $state->get_sitecode() . ".  Creating a SITE outage\n"; 
				$newinc = Incident::create([
					'name'		=>	$state->get_sitecode(),
					'type_id'	=>	$inctype->id,
				]);
				//Assign all states to this new incident.
				foreach($siteStates as $sitestate)
				{
					foreach($sitestate as $sitedevicestate)
					{
						print "Adding alert state " . $sitedevicestate->device_name . " to Site incident\n";
						$sitedevicestate->incident_id = $newinc->id;
						$sitedevicestate->processed = 1;
						$sitedevicestate->save();
					}
				}
			}
		}
	}
/* 
	//Check if a new DEVICE_NETWORK incident needs to be created for a single network device.
	public static function processNewDeviceNetwork()
	{
		//Get all unassigned states that are older than the TIMER_STATE_SAMPLING_DELAY
		$states = State::whereNull("incident_id")->where('updated_at',"<",Carbon::now()->subMinutes(env('TIMER_STATE_SAMPLING_DELAY')))->where("type","NETWORK")->get();
		foreach($states as $state)
		{
			$state->refresh();
			//Find the correct incidentType.  If not found, exit function.
			$inctype = IncidentType::where("name","DEVICE_NETWORK_LOW")->first();
			if(!$inctype)
			{
				return;
			}
			//Get all unassigned states that match this states sitecode.
			$siteStates = $state->getUnassignedUniqueDeviceSiteStates();
			//If there is only 1 device for this site AND it is not resolved, open a new device incident.
			if($siteStates->count() == 1 && $siteStates->first()->where('resolved',0)->count() > 0)
			{
				$newinc = Incident::create([
					'name'		=>	$state->device_name,
					'type_id'	=>	$inctype->id,
				]);
				//Find all unassigned states with thie device name and assign them to new incident.
				$devicestates = State::whereNull("incident_id")->where('device_name',$state->device_name)->get();
				foreach($devicestates as $devicestate)
				{
					$devicestate->incident_id = $newinc->id;
					$devicestate->processed = 1;
					$devicestate->save();
				}
			}
		}
	}

	//Check if a new DEVICE_SERVER incident needs to be created for a single server device.
	public static function processNewDeviceServer()
	{
		//Get all unassigned states that are older than the TIMER_STATE_SAMPLING_DELAY
		$states = State::whereNull("incident_id")->where('updated_at',"<",Carbon::now()->subMinutes(env('TIMER_STATE_SAMPLING_DELAY')))->where("type","SERVER")->get();
		foreach($states as $state)
		{
			$state->refresh();
			//Find the correct incidentType.  If not found, exit function.
			$inctype = IncidentType::where("name","DEVICE_SERVER_HIGH")->first();
			if(!$inctype)
			{
				return;
			}
			//Get all unassigned states that match this states sitecode.
			$siteStates = $state->getUnassignedUniqueDeviceSiteStates();
			//If there is only 1 device for this site AND it is not resolved, open a new device incident.
			if($siteStates->count() == 1 && $siteStates->first()->where('resolved',0)->count() > 0)
			{
				$newinc = Incident::create([
					'name'		=>	$state->device_name,
					'type_id'	=>	$inctype->id,
				]);
				foreach($siteStates as $sitestate)
				{
					foreach($sitestate as $sitedevicestate)
					{
						$sitedevicestate->incident_id = $newinc->id;
						$sitedevicestate->processed = 1;
						$sitedevicestate->save();
					}
				}
			}
		}
	}
 */
	//Check if a new DEVICE_NETWORK incident needs to be created for a single network device.
	public static function processNewDevice()
	{
		print "Processing New Devices\n";
		//Get all unassigned states that are older than the TIMER_STATE_SAMPLING_DELAY
		$states = State::whereNull("incident_id")->where('updated_at',"<",Carbon::now()->subMinutes(env('TIMER_STATE_SAMPLING_DELAY')))->get();
		foreach($states as $state)
		{
			$state->refresh();
			//Find the correct incidentType.  If not found, exit function.
			if($state->type == "NETWORK")
			{
				$type = "DEVICE_NETWORK_LOW";
			}
			if($state->type == "SERVER")
			{
				$type = "DEVICE_SERVER_MEDIUM";
			}
			$inctype = IncidentType::where("name",$type)->first();
			if(!$inctype)
			{
				return;
			}
			//Get all unassigned states that match this states sitecode.
			$siteStates = $state->getUnassignedUniqueDeviceSiteStates();
			//If there is only 1 device for this site AND it is not resolved, open a new device incident.
			if($siteStates->count() == 1 && $siteStates->first()->where('resolved',0)->count() > 0)
			{
				print "Found at least 1 alert state for device " . $state->device_name . ".  Creating a new Device incident\n";
				$newinc = Incident::create([
					'name'		=>	$state->device_name,
					'type_id'	=>	$inctype->id,
				]);
				//Find all unassigned states with thie device name and assign them to new incident.
				$devicestates = State::whereNull("incident_id")->where('device_name',$state->device_name)->get();
				foreach($devicestates as $devicestate)
				{
					print "Adding alert state " . $devicestate->device_name . "to device incident\n";
					$devicestate->incident_id = $newinc->id;
					$devicestate->processed = 1;
					$devicestate->save();
				}
			}
		}
	}

	//Check for any resolved states that never created an incident.  If stale time expired, delete them.
	public static function processStale()
	{
		print "Processing Stale States\n";
		$states = State::whereNull("incident_id")->where('resolved',1)->where('updated_at',"<",Carbon::now()->subMinutes(env('TIMER_DELETE_STALE_STATES')))->get();
		foreach($states as $state)
		{
			print "Deleting stale alert state " . $state->device_name . ".\n";
			$state->refresh();
			$state->delete();
		}
	}

}
