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
	
	public function get_unassigned_site_states()
	{
		return State::where('name', 'like', '%' . $this->get_sitecode() . '%')->where('type','device')->whereNull("incident_id")->get();
	}
	
	public function get_site_states()
	{
		return State::where('name', 'like', '%' . $this->get_sitecode() . '%')->where('type','device')->get();	
	}
	
	public function get_unresolved_site_states()
	{
		return State::where('name', 'like', '%' . $this->get_sitecode() . '%')->where('type','device')->where('resolved',0)->get();
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
		} elseif($deviceincident = Incident::where('type', "device")->where('name', $this->name)->first()){
			return $deviceincident;
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
			//Create a device incident for single device.
			if($incident = $this->create_incident($this->name, 'device'))
			{
				$this->incident_id = $incident->id;
				$this->processed = 1;
				$this->save();
			}
		}
		return $incident;
	}
	
	//check for existing incident and create one of proper type if needed!
	public function process_state_incident()
	{
		//Find any existing incidents.
		$incident = $this->find_incident();
		//If no incident is found
		if (!$incident)
		{
			//Find any other states with same site code.
			$sitestates = $this->get_site_states();
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
	public function comment_ticket($msg)
	{
		$incident = Incident::find($this->incident_id);
		if($incident)
		{
			$ticket = $incident->get_ticket();
			if($ticket)
			{
				print $this->name . " ADD COMMENT: " . $msg . "\n";
				$ticket->add_comment($msg);
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
		//Find an existing incident for this state
		$incident = $this->find_incident();
		//if there is an existing incident for this state
		if($incident)
		{
			print "INCIDENT!\n";
			//If this state does NOT have an incident ID assigned to it
			if(!$this->incident_id)
			{
				print "INCIDENT ASSIGNED!\n";
				//get the snow ticket from the incident
				$ticket = $incident->get_ticket();
				//Generate appropriate comment
				if($this->resolved == 1)
				{
					$msg = "Received a RECOVERY notification for device " . $this->name . ".  Adding state tracking to this incident.";
				} else {
					$msg = "Received an ALERT notification for device " . $this->name . ".  Adding state tracking to this incident.";
				}
				//Add a comment to the snow ticket regarding new STATE.
				$ticket->add_comment($msg);
				//Assign the incident ID to this state
				$this->incident_id = $incident->id;
				$this->processed = 1;
				$this->save();
			}
		//If there is no incident and sampling delay timer has passed
		} elseif ($this->updated_at < Carbon::now()->subMinutes(env('TIMER_STATE_SAMPLING_DELAY'))){
			//Create a new incident
			$incident = $this->process_state_incident();
			//If an incident was created successfully
			if ($incident)
			{
				//mark state as processed
				$this->processed = 1;
				$this->save();
			//If an incident was NOT created successfully
			} else {
				//Check for a stale state (single device that is recovered before an incident could be made)
				$this->process_stale();
			}
		}
	}
	
	public function process2()
	{
		if($this->processed == 0)
		{
			if(!$this->incident_id)
			{
				$incident = $this->find_incident();
				$ustates = $this->get_unassigned_site_states();
				if($incident)
				{
					if($incident->type == "site")
					{
						$msg = "The following devices have been added to this incident:\n";
						foreach($ustates as $ustate)
						{
							$msg .= $ustate->name . "\n";
							$ustate->incident_id = $incident->id;
							$ustate->processed = 1;
							$ustate->save();
						}
						if($ticket = $incident->get_ticket())
						{
							$ticket->add_comment($msg);
						}
					} elseif($incident->type == "device") {
						$this->incident_id = $incident->id;
						$this->processed = 1;
						$this->save();
					}
				} else {
					$unstates = $this->get_unresolved_site_states();
					if($unstates->count() > 0 && $this->updated_at < Carbon::now()->subMinutes(env('TIMER_STATE_SAMPLING_DELAY')))
					{
						if($incident = $this->create_new_incident())
						{
							print "Created incident " . $incident->id . "...\n";
						}
					} elseif($unstates->count() == 0 && $this->updated_at->lt(Carbon::now()->subMinutes(env('TIMER_DELETE_STALE_STATES')))){
						//DELETE IT!
						print "Deleting Stale Entry: " . $this->name . "\n";
						$this->delete();
					}
				}
			}
		}
	}
}