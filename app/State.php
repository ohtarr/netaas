<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Incident;
use Carbon\Carbon;

class State extends Model
{
	use SoftDeletes;
	protected $fillable = ['name','type','state','processed','resolved'];

	//Convert state name to site code
	public function get_sitecode()
	{
		//grab the first 8 characters of our name.  This is our sitecode!
		$sitecode = substr($this->name,0,8);
        if (!$sitecode) {
            throw new \Exception('Site code unknown for state id ' . $this->id . ' with name ' . $this->name);
        }
		//Return the sitecode!
		return $sitecode;
	}
	
	public function get_site_states()
	{
		return State::where('name', 'like', '%' . $this->get_sitecode() . '%')->where('type','device')->get();	
	}
	
	public function get_unassigned_site_states()
	{
		return State::where('name', 'like', '%' . $this->get_sitecode() . '%')->where('type','device')->whereNull("incident_id")->get();
	}

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
		//find any existing DEVICE incidents first.
		} elseif($deviceincident = Incident::where('type', "device")->where('name', $this->name)->first()){
			return $deviceincident;
		//Now look for any existing SITE incidents.
		} elseif($siteincident = Incident::where('type', "site")->where('name', $this->get_sitecode())->first()){
			return $siteincident;
		} else {
			//No found incidents = Return null!
			print "No incident found!\n";
			return null;
		}
	}
	
	public function create_new_incident()
	{
		$usitestates = $this->get_unassigned_site_states();
		//If there is more than 1 device with same site code in state table
		if($usitestates->count() > 1)
		{
			//Create a SITE incident for multiple devices, even if they are resolved.
			if($incident = $this->create_incident($this->get_sitecode(), 'site'))
			{
				foreach($usitestates as $usitestate)
				{
					$usitestate->incident_id = $incident->id;
					$usitestate->processed = 1;
					$usitestate->save();
				}
			}
		//If there is only 1 device and it is NOT resolved
		} else {
			if($this->resolved == 0)
			{
				//Create a device incident for single device.
				if($incident = $this->create_incident($this->name, 'device'))
				{
					$this->incident_id = $incident->id;
					$this->processed = 1;
					$this->save();
				}
			}
		}
	}

	//Created an incident in the incident table
	public function create_incident($name, $type)
	{
		print "Creating a new incident for " . $this->name . "\n";
		//Create a new incident with provided name and type.
		$newinc = Incident::create([
			'name'		=>	$name,
			'type'		=>	$type,
		]);
		//Return the new incident.
		return $newinc;
	}

}