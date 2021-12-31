<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Incident;
use Carbon\Carbon;
use App\ServiceNowLocation;
use Illuminate\Support\Facades\Log;

class State extends Model
{
	use SoftDeletes;
	protected $fillable = ['device_name','type','processed','resolved','entity_name','entity_type','entity_desc'];

	//Convert state name to site code
	public function get_sitecode()
	{
		//grab the first 8 characters of our name.  This is our sitecode!
		$sitecode = substr($this->device_name,0,8);
        if (!$sitecode) {
			//throw new \Exception('Site code unknown for state id ' . $this->id . ' with name ' . $this->name);
			print "No valid site code found!\n";
			return null;
        }
		//Return the sitecode!
		return $sitecode;
	}

	public function get_location()
	{
		$location = null;
		try
		{
			$location = ServiceNowLocation::where("name","=",$this->get_sitecode())->first();
		} catch(\Exception $e) {
		
		}
		if (!$location) {
			$message = "STATE ID: " . $this->id . " Unable to locate ServiceNowLocation " . $this->get_sitecode();
			print $message . "\n";
			Log::info($message);
        }
		//Return the sitecode!
		return $location;
	}

	/*
	public function get_site_states()
	{
		return State::where('name', 'like', '%' . $this->get_sitecode() . '%')->where('type','device')->get();	
	}
	/**/
	/*
	public function get_unassigned_site_states()
	{
		return State::where('name', 'like', '%' . $this->get_sitecode() . '%')->whereNull("incident_id")->get();
	}
	/**/
/*
	public static function getAllUnassignedSites()
	{
		$sites = [];
		$allstates = State::whereNull("incident_id")->get();
		$devices = $allstates->groupBy('device_name');
		foreach($devices as $device)
		{
			foreach($device as $entity)
			{
				$sites[] = substr($entity->device_name,0,8);
			}
		}
		$sites = array_unique($sites);
		return $sites;
	}
/**/
	public static function getUnassignedStates()
	{
		return State::whereNull("incident_id")->get();
	}

	public static function getUnassignedStatesDelayed()
	{
		return State::whereNull("incident_id")->where('updated_at',"<",Carbon::now()->subMinutes(env('TIMER_STATE_SAMPLING_DELAY')))->get();
	}

	public static function getUnassignedResolvedStaleStates()
	{
		return State::whereNull("incident_id")->where('resolved',1)->where('updated_at',"<",Carbon::now()->subMinutes(env('TIMER_DELETE_STALE_STATES')))->get();
	}

/* 	public function getUnassignedSiteStatesPerDevice()
	{
		$states = State::where('device_name', 'like', '%' . $this->get_sitecode() . '%')->whereNull("incident_id")->get();
		return $states->groupBy('device_name');
	} */

	public function getUnassignedSiteStatesPerDevice($type = null)
	{
		$query = State::where('device_name', 'like', '%' . $this->get_sitecode() . '%')->whereNull("incident_id");
		if($type)
		{
			$query = $query->where("type",$type);
		}
		$states = $query->get();
		return $states->groupBy('device_name');
	}

	public function getUnresolvedUnassignedSiteStates()
	{
		return State::where('device_name', 'like', '%' . $this->get_sitecode() . '%')->whereNull("incident_id")->where("resolved",0)->get();
	}
/*	
	public function getUnresolvedUnassignedUniqueDeviceSiteStates()
	{
		$states = State::where('device_name', 'like', '%' . $this->get_sitecode() . '%')->whereNull("incident_id")->where("resolved",0)->get();
		return $states->groupBy('device_name');
	}
/**/
/*
	//Locate an existing incident for this state.
	public function find_incident()
	{
		//If we have an assigned incident_id in our state
		if($this->incident_id)
		{
			//If the incident_id coorelates to an actual incident, return it, otherwise remove the ID and let it find a new ticket.
			if($incident = Incident::find($this->incident_id))
			{
				return $incident;
			}
		//find any existing incidents first.
		} elseif($deviceincident = Incident::where('name',$this->device_name)->first()){
			return $deviceincident;
		//Now look for any existing SITE incidents.
		} elseif($siteincident = Incident::where('type', "site")->where('name', $this->get_sitecode())->first()){
			return $siteincident;
		} elseif($deviceincident = Incident::where('type','company')->first()){
			return $deviceincident;
		} else {
			//No found incidents = Return null!
			print "No incident found!\n";
			return null;
		}
	}
/**/
/*	
	public function create_new_incident()
	{
		$sites = $this->getAllUnassignedSites();		
		//$usitedevices = $this->get_unassigned_site_states()->groupBy('name');
		$usitedevices = $this->getUnassignedSiteStatesPerDevice();
		//If there is more than 1 device with same site code in state table
		if(count($sites) > env("COMPANY_OUTAGE_COUNT"))
		{
			$type = "company";
			$name = "company";
		} elseif($usitedevices->count() > 1) {
			$type = "site";
			$name = $this->get_sitecode();
		} else {
			$type = "device";
			$name = $this->device_name;
		}
		//Create an incident is it is a SITE, or a device that is still unresolved
		if ($type == "site" || $type == "company" || $this->resolved == 0)
		{
			$incident = $this->create_incident($name, $type);
			if($incident)
			{
				//Loop through each DEVICE
				foreach($usitedevices as $usitedevice)
				{
					//Loop through each device STATE
					foreach($usitedevice as $usitedevicestate)
					{
						$usitedevicestate->incident_id = $incident->id;
						$usitedevicestate->processed = 1;
						$usitedevicestate->save();
					}
				}		
			}
		}
	}
/**/
/*
	//Created an incident in the incident table
	public function create_incident($name, $type)
	{
		print "Creating a new incident for " . $this->device_name . "\n";
		//Create a new incident with provided name and type.
		$newinc = Incident::create([
			'name'		=>	$name,
			'type'		=>	$type,
		]);
		//Return the new incident.
		return $newinc;
	}
/**/
}