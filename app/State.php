<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Incident;
use Carbon\Carbon;

class State extends Model
{
	use SoftDeletes;
	protected $fillable = ['name','type','state','processed'];

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

	/*
	//Locate all other STATES with same site code, +/- 10 minutes ago, do not include self..
	public function find_site_states()
	{
		//Get our site code
		$sitecode = $this->get_sitecode();
		$time1 = $this->created_at->subMinutes(10);
		$time2 = $this->created_at->addMinutes(10);
		//Search for any states that have our sitecode in their name, type device, and were created 10 minutes before or after our state was created.  Do not include ourself.
		$sitestates = State::where('name', 'like', '%' . $sitecode . '%')->where('type','device')->whereBetween('created_at', array($time1, $time2))->where('incident_id', null)->where('id',"<>", $this->id)->get();
		//Return collection of states that match!
		return $sitestates;
	}
	/**/
	
	/*
	public function get_site_states()
	{
		return State::where('name', 'like', '%' . $this->get_sitecode() . '%')->where('type','device')->get();
	}
	/**/
	
	public function get_recent_site_states()
	{
		$time1 = Carbon::parse($this->updated_at)->subMinutes(10);
		$time2 = Carbon::parse($this->updated_at)->addMinutes(10);
		return State::where('name', 'like', '%' . $this->get_sitecode() . '%')->where('type','device')->whereBetween('created_at', array($time1, $time2))->get();
	}
	
	//Locate an existing incident for this state.
	public function find_incident()
	{
		//If we have an assigned incident_id in our state
		if($this->incident_id)
		{
			//find it and return that incident
			$incident = Incident::find($this->incident_id);
			return $incident;
		}
		//Check for any existing DEVICE incident that has our device name
		$deviceincident = Incident::where('type', "device")->where('name', $this->name)->first();
		//If a device incident exists with our device name
		if($deviceincident)
		{
			//Save the incident ID in our state incident_id and return the incident.
			print "Found DEVICE incident ID: " . $deviceincident->id . "\n";
			$this->incident_id = $deviceincident->id;
			$this->save();
			return $deviceincident;
		}
		//Check for any exisiting SITE incidents that have our site id in the name
		$siteincident = Incident::where('type', "site")->where('name', $this->get_sitecode())->first();
		//If a site incident exists with our site code
		if($siteincident)
		{
			//Save the incident ID in our state incident_id and return the incident.
			print "Found SITE incident ID: " . $siteincident->id . "\n";
			$this->incident_id = $siteincident->id;
			$this->save();
			return $siteincident;
		}
		//No found incidents = Return null!
		print "No incident found!\n";
		return null;
	}
	
	//check for existing incident and create one of proper type if needed!
	public function process_state_incident()
	{
		//Find any existing incidents.
		$incident = $this->find_incident();
		//Find any other states with same site code.
		$sitestates = $this->get_recent_site_states();
		//If no incident is found
		if (!$incident)
		{
			//If there is more than 1 device with same site code in state table
			if($sitestates->count() > 1)
			{
				//Create a SITE incident for multiple devices!
				$incident = $this->create_incident($this->get_sitecode(), 'site');
			//If there is only 1 device and it is NOT resolved
			} elseif (!$this->resolved) {
				//Create a device incident for single device.
				$incident = $this->create_incident($this->name, 'device');
			}
		}
		//Assign the incident id to the states incident_id field.
		if($incident)
		{
			$this->incident_id = $incident->id;
			$this->save();
		}
		//If this state is NOT resolved,
		if (!$this->resolved)
		{
			//make sure the incident is not resolved.
			$incident->resolved = 0;
			$incident->save();
		}
		//Return the incident
		return $incident;
	}

	//Created an incident in the incident table
	public function create_incident($name, $type)
	{
		print "Creating a new incident....";
		//Create a new incident with provided name and type.
		$newinc = Incident::create([
			'name'		=>	$name,
			'type'		=>	$type,
		]);
		//Return the new incident.
		return $newinc;
	}

	//Function to update a TICKET with comment.
	public function comment_ticket($comment)
	{
		$incident = Incident::find($this->incident_id);
		if($incident)
		{
			$ticket = Ticket::where('sysid', $incident->ticket)->first();
			//COMMENT CODE HERE!
		}
	}

	//Check if this is a stale state that is no longer needed.  DELETE if stale.
	public function process_stale()
	{
		//Check for existing incident
		$incident = $this->find_incident();
		//Time 30 minutes prior to last update
		$mins = Carbon::now()->subMinutes(30);
		//If it hasn't been updated in 30 mintes, there is no incident, and it is marked RESOLVED
		if(Carbon::parse($this->updated_at)->lt($mins) && !$incident && $this->resolved)
		{
			//DELETE IT!
			$this->delete();
		}
	}

	public function process()
	{
		print "Processing State " . $this->name . "!!\n";
		$incident = $this->process_state_incident();
		if (!$incident)
		{
			$this->process_stale();
		} else {
			$this->processed = 1;
			$this->save();
		}
	}
	
}
