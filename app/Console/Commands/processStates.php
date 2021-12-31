<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\State;
use App\Incident;
use App\IncidentType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
		$this->processNewDevice();
		$this->processStale();
	}

	//Look for existing DEVICE incident, if exists assign states to it.
	public static function processExistingDevice()
	{
		$message = "processStates Processing Existing Devices";
		print $message . "\n";
		Log::info($message);
		//Get all states that are not already assigned to an incident and foreach through them.
		$states = State::getUnassignedStates();
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
					$message = "processStates Found existing Incident with ID " . $deviceincident->id . " for state " . $devicestate->device_name . " with ID " . $devicestate->id . " Adding to existing incident.";
					print $message . "\n";
					Log::info($message);
					$devicestate->incident_id = $deviceincident->id;
					$devicestate->save();
				}
			}
		}
	}

	//look for an existing network SITE incident.  If one exists, assign all related states to it.
	public static function processExistingSite()
	{
		$message = "processStates Processing Existing Sites";
		print $message . "\n";
		Log::info($message);
		//Find all unassigned states and Check if an existing site incident exists for it.
		$states = State::getUnassignedStates();
		foreach($states as $state)
		{
			$state->refresh();
			$siteincident = Incident::where('name', $state->get_sitecode())->first();

			if($siteincident)
			{
				//If a site incident exists already, find all states that match site name and assign them to the incident.
				$sitestates = $state->getUnassignedSiteStatesPerDevice();
				foreach($sitestates as $sitestate)
				{
					foreach($sitestate as $sitedevicestate)
					{
						$message = "processStates Found existing Incident ID " . $siteincident->id . " for " . $sitedevicestate->device_name . " with ID " . $sitedevicestate->id . " Adding to existing incident.";
						print $message . "\n";
						Log::info($message);	
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
		$message = "processStates Processing Existing Company Outage";
		print $message . "\n";
		Log::info($message);
		//Find the incidentType for COMPANY_CRITICAL.  If it doesn't exist, exit function.
		$inctype = IncidentType::getIncidentTypeByName("COMPANY_CRITICAL");
		if(!$inctype)
		{
			return;
		}
		//Find any existing COMPANY_CRITICAL incidents.  If it exists, assign all unassigned states to it.
		$companyincident = Incident::where('type_id',$inctype->id)->first();
		if($companyincident)
		{
			$states = State::getUnassignedStates();
			foreach($states as $state)
			{
				$message = "processStates Adding state " . $state->device_name . " with ID " . $state->id . " to existing company outage with ID " . $companyincident->id;
				print $message . "\n";
				Log::info($message);
				$state->incident_id = $companyincident->id;
				$state->save();
			}
			return $companyincident;
		}
	}

	//Check if a new COMPANY incident needs to be created.
	public static function processNewCompany()
	{
		$message = "processStates Processing New company outage";
		print $message . "\n";
		Log::info($message);
		//Look for COMPANY_CRITICAL incidentType.  If it doesn't exist, exit the function.
		$inctype = IncidentType::getIncidentTypeByName("COMPANY_CRITICAL");
		$states = State::getUnassignedStatesDelayed();
		if($states->isEmpty())
		{
			return;
		}
		//Get all unassigned states.
		$states = State::getUnassignedStates();
		$sites = [];
		$devices = $states->groupBy('device_name');
		//print_r($devices);
		foreach($devices as $name => $device)
		{
			//List of SITES for each state.  Used to determine number of SITES currently in alarm state.
			$sites[] = substr($name,0,8);
		}
		$sites = array_unique($sites);  //Remove duplicates to get an accurate site count.
		//If the count of unassigned sites in alert state is larger than the configured COMPANY_OUTAGE_COUNT, create a company outage incident
		if(count($sites) < env("COMPANY_OUTAGE_COUNT"))
		{
			return;
		}
		$message = "processStates Detected more than " . env("COMPANY_OUTAGE_COUNT") . " alert states.  Creating a new COMPANY OUTAGE.";
		print $message . "\n";
		Log::info($message);
		$newinc = Incident::create([
			'name'		=>	"company",
			'type_id'	=>	$inctype->id,
		]);
		//Assign ALL unassigned states to this company outage incident.
		foreach($states as $state)
		{
			$message = "processStates Adding state " . $state->device_name . " with ID " . $state->id . " to company outage incident ID " . $newinc->id;
			print $message . "\n";
			Log::info($message);
			$state->incident_id = $newinc->id;
			$state->processed = 1;
			$state->save();
		}
		return $newinc;
	}

	//Check if a new SITE incident needs to be created.
	public static function processNewSite()
	{
		$message = "processStates Processing New Sites";
		print $message . "\n";
		Log::info($message);
		//Get all states that are unassigned and older than TIMER_STATE_SAMPLING_DELAY
		$states = State::getUnassignedStatesDelayed();
		foreach($states as $state)
		{
			$state->refresh();
			//Get all NETWORK states that match this states sitecode
			$siteNetworkStates = $state->getUnassignedSiteStatesPerDevice("NETWORK");
			$siteStates = $state->getUnassignedSiteStatesPerDevice();
			$unresolvedSiteStates = $state->getUnresolvedUnassignedSiteStates();
			//If there is more than 1 state that match sitecode, create a SITE incident.
			if($siteNetworkStates->count() >= 1 && $siteStates->count() > 1 && $unresolvedSiteStates->count() > 0)
			{
				$message = "processStates Detected more than 1 alert state from site " . $state->get_sitecode() . ". Creating a SITE outage";
				print $message . "\n";
				Log::info($message);
				//Get ServiceNowLocation for this state.
				$location = $state->get_location();
				//assume MEDIUM priority.
				$inctype = IncidentType::getIncidentTypeByName("SITE_MEDIUM");
				//If we have a valid location, get the priority of the site to determine incident priority.
				//Overwrite inctype to SITE_HIGH if needed.
				if($location)
				{
					if($location->u_priority == 2)
					{
						$inctype = IncidentType::getIncidentTypeByName("SITE_HIGH");
					}
				}
				//If we can't find the proper IncidentType, exit the function.
				if(!$inctype)
				{
					continue;
				}
				$newinc = Incident::create([
					'name'		=>	$state->get_sitecode(),
					'type_id'	=>	$inctype->id,
				]);
				//Assign all states to this new incident.
				foreach($siteStates as $sitestate)
				{
					foreach($sitestate as $sitedevicestate)
					{
						$message = "processStates Adding alert state " . $sitedevicestate->device_name . " to Site incident ID " . $newinc->id;
						print $message . "\n";
						Log::info($message);
						$sitedevicestate->incident_id = $newinc->id;
						$sitedevicestate->processed = 1;
						$sitedevicestate->save();
					}
				}
			} else {
				$message = "processStates SITE:" . $site->name . " ID:" . $site->id . " Did not detect more than 1 alert for site.  SiteNetworkStates: " . $siteNetworkStates->count() . " SiteStates: " . $siteStates->count() . "unresolvedSiteStates " . $unresolvedSiteStates->count();
				print $message . "\n";
				Log::info($message);
			}
		}
	}

	//Check if a new DEVICE_NETWORK incident needs to be created for a single network device.
	public static function processNewDevice()
	{
		$message = "processStates Processing New Devices";
		print $message . "\n";
		Log::info($message);
		//Get all unassigned states that are older than the TIMER_STATE_SAMPLING_DELAY
		$states = State::getUnassignedStatesDelayed();
		foreach($states as $state)
		{
			$state->refresh();
			//Get all unassigned states that match this states sitecode.
			$siteStates = $state->getUnassignedSiteStatesPerDevice();
			$unresolvedSiteStates = $state->getUnresolvedUnassignedSiteStates();
			$incidents = Incident::where('name', $state->device_name)->get();
			//If there is only 1 device for this site AND it is not resolved AND there is no current incident, open a new device incident.
			//if($siteStates->count() == 1 && $unresolvedSiteStates->count() > 0 && $incidents->count() == 0)
			if($unresolvedSiteStates->count() > 0)
			{
				//Find the correct incidentType.  If not found, exit function.
				switch ($state->type) {
					case "NETWORK":
						$type = "DEVICE_NETWORK_LOW";
						break;
					case "SERVER_WINDOWS":
						$type = "DEVICE_SERVER_MEDIUM";
						break;
					case "SERVER_NASUNI":
						$type = "DEVICE_SERVER_MEDIUM";
						break;
					case "SERVER_ESXI":
						$type = "DEVICE_SERVER_MEDIUM";
						break;
					case "AZURE":
						$type = "DEVICE_AZURE_MEDIUM";
						break;
				}
				$inctype = IncidentType::getIncidentTypeByName($type);
				if(!$inctype)
				{
					continue;
				}
				$message = "processStates Found at least 1 alert state for device " . $state->device_name . ". Creating a new Device incident";
				print $message . "\n";
				Log::info($message);
				$newinc = Incident::create([
					'name'		=>	$state->device_name,
					'type_id'	=>	$inctype->id,
				]);
				$message = "processStates Created new incident ID " . $newinc->id . " for device " . $state->device_name;
				print $message . "\n";
				Log::info($message);
				//Find all unassigned states with this device name and assign them to new incident.
				//$devicestates = State::whereNull("incident_id")->where('device_name',$state->device_name)->get();
				$devicestates = $siteStates->first();
				//print_r($devicestates);
				foreach($devicestates as $devicestate)
				{
					$message = "processStates Adding alert state " . $devicestate->device_name . " to device incident ID " . $newinc->id;
					print $message . "\n";
					Log::info($message);
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
		$message = "processStates Processing Stale States";
		print $message . "\n";
		Log::info($message);
		$states = State::getUnassignedResolvedStaleStates();
		foreach($states as $state)
		{
			$message = "processStates Deleting stale alert state " . $state->device_name;
			print $message . "\n";
			Log::info($message);

			$state->refresh();
			$state->delete();
		}
	}

}
