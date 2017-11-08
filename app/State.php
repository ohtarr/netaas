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
	
	public function get_unassigned_site_states()
	{
		return State::where('name', 'like', '%' . $this->get_sitecode() . '%')->where('type','device')->whereNull("incident_id")->get();
	}
	
	public function get_site_states()
	{
		return State::where('name', 'like', '%' . $this->get_sitecode() . '%')->where('type','device')->get();	
	}

	public function find_device_incident()
	{
		//Check for any existing DEVICE incident that has our device name
		$deviceincident = Incident::where('type', "device")->where('name', $this->name)->first();
		//If a device incident exists with our device name
		if($deviceincident)
		{
			//Save the incident ID in our state incident_id and return the incident.
			$this->incident_id = $deviceincident->id;
			$this->save();
			return $deviceincident;
		}	
	}
	
	public function find_site_incident()
	{
		//Check for any exisiting SITE incidents that have our site id in the name
		$siteincident = Incident::where('type', "site")->where('name', $this->get_sitecode())->first();
		//If a site incident exists with our site code
		if($siteincident)
		{
			//Save the incident ID in our state incident_id and return the incident.
			$this->incident_id = $siteincident->id;
			$this->save();
			return $siteincident;
		}	
	}
	
	//Locate an existing incident for this state.
	public function find_incident()
	{
		//If we have an assigned incident_id in our state
		if($this->incident_id)
		{
			//find it and return that incident
			$incident = Incident::find($this->incident_id);
			//If the incident_id coorelates to an actual incident, return it, otherwise remove the ID and let it find a new ticket.
			if($incident)
			{
				return $incident;
			} else {
				$this->incident_id = null;
				$this->save();
			}
		}
		$incident = $this->find_device_incident();
		if($incident)
		{
			print "Found DEVICE incident ID: " . $incident->id . "\n";
			return $incident;
		}
		$incident = $this->find_site_incident();
		if($incident)
		{
			print "Found SITE incident ID: " . $incident->id . "\n";
			return $incident;
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
		$sitestates = $this->get_site_states();
		//If no incident is found
		if (!$incident)
		{
			//If there is more than 1 device with same site code in state table
			if($sitestates->count() > 1)
			{
				//Create a SITE incident for multiple devices, even if they are resolved.
				$incident = $this->create_incident($this->get_sitecode(), 'site');
				foreach($sitestates as $sitestate)
				{
					print "SITE!\n";
					$sitestate->incident_id = $incident->id;
					$sitestate->save();
				}
			//If there is only 1 device and it is NOT resolved
			} elseif (!$this->resolved) {
				print "DEVICE!\n";
				//Create a device incident for single device.
				$incident = $this->create_incident($this->name, 'device');
				$this->incident_id = $incident->id;
				$this->save();
			}
		}
		//Return the incident
		return $incident;
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

/*
	//Function to update a TICKET with comment.
	public function comment_ticket($comment)
	{
		$incident = Incident::find($this->incident_id);
		if($incident)
		{
			$ticket = $incident->get_ticket();
			if($ticket)
			{
				print $this->name . " ADD COMMENT: " . $comment . "\n";
				$ticket->add_comment($comment);
			}
		}
	}
/**/

	//Check if this is a stale state that is no longer needed.  DELETE if stale.
	public function process_stale()
	{
		print "processing Stale States \n";
		//Check for existing incident
		$incident = $this->find_incident();
		//Time 30 minutes prior to last update
		$mins = Carbon::now()->subMinutes(env('TIMER_DELETE_STALE_STATES'));
		//If it hasn't been updated in 30 mintes, there is no incident, and it is marked RESOLVED
		if($this->updated_at->lt($mins) && !$incident && $this->resolved)
		{
			//DELETE IT!
			print "Deleting Stale Entry: " . $this->name . "\n";
			$this->delete();
		}
	}

	public function process()
	{
		print "Processing State " . $this->name . "!!\n";
		if($this->incident_id || $this->updated_at  < Carbon::now()->subMinutes(env('TIMER_STATE_SAMPLING_DELAY')))
		{
			$incident = $this->process_state_incident();
			if (!$incident)
			{
				$this->process_stale();
			} else {
				$this->processed = 1;
				$this->save();
			}
		} else {
			print $this->name . " STANDING BY!\n";
		}
	}
}