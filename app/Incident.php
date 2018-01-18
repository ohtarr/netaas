<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Ticket;
use App\State;
use App\ServiceNowIncident;
use App\ServiceNowLocation;
use Carbon\Carbon;
use GuzzleHttp\Client as GuzzleHttpClient;

class Incident extends Model
{
	use SoftDeletes;
	protected $fillable = ['name','type'];

	protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
		'last_opened',
		'called_oncall',
		'called_sup'
    ];

	//close this incident
	public function close()
	{
		$this->resolved = 1;
		$this->save(); 
	}

	public function isOpen()
	{
		if ($this->resolved == 1 || $this->trashed())
		{
			return false;
		} else {
			return true;
		}
	}
	
	public function purgeStates()
	{
		$states = $this->get_states();
		//Delete all states associated to this internal incident
		foreach($states as $state)
		{
			$state->delete();
		}
		//DELETE this internal incident from database.	
	}
	
	public function purge()
	{
		$this->purgeStates();
		$this->delete();
	}
		
	public function getUrgency()
	{
		$location = $this->get_location();
		if($this->type == "company")
		{
			$urgency = 1;
		} elseif($this->type == "site") {
			if($location)
			{
				if($location->u_priority == 2)
				{
					$urgency = 1;			
				} else {
					$urgency = 2;
				}
			} else {
				$urgency = 2;
			}
		} else {
			$urgency = 3;
		}
		return $urgency;
	}
	
	public function getImpact()
	{
		if($this->type == "company")
		{
			return 1;
		} else {
			return 2;
		}	
	}
	
	public function get_location()
	{
		//grab the first 8 characters of our name.  This is our sitecode!
		$sitecode = strtoupper(substr($this->name,0,8));
		$location = null;
		try
		{
			$location = ServiceNowLocation::where("name","=",$sitecode)->first();
		} catch(\Exception $e) {
		
		}
		return $location;
	}

	public function getStateStatus()
	{
		$description = "";
		foreach($this->getUniqueDeviceStates() as $name => $device)
		{
			$description .= "DEVICE " . $name . ":\n";
			foreach($device as $state)
			{
				$state->processed = 1;
				$state->save();
				$description .= $state->type . "\t";
				$description .= $state->entity_name . "\t";
				if($state->resolved == 0)
				{
					$description .= "DOWN\t";
				} else {
					$description .= "UP\t";
				}
				$description .= $state->entity_desc . "\n";
			}
			$description .= "\n";
		}
		return $description;
	}
	
	public function createLocationDescription()
	{
		$description = "";
		if($this->type != "company")
		{
			$location = $this->get_location();
			if($location)
			{
				$description .= "*****************************************************\n";
				$description .= "SITE NAME: " . $location->name . "\n\n";
				$description .= "Display Name: " . $location->u_display_name . "\n\n";
				$description .= "Description: " . $location->description . "\n\n";
				$description .= "Address: " . $location->street . ", " . $location->city . ", " . $location->state . ", " . $location->zip . "\n\n";
				$description .= "Comments: \n" . $location->u_comments . "\n\n";

				$contact = $location->getContact();
				if($contact)
				{
					$description .= "*****************************************************\n";
					$description .= "Site Contact: \nName: " . $contact->name . "\nPhone: " . $contact->phone . "\nMobile: " . $contact->mobile_phone . "\nEmail: " . $contact->email . "\n";			
				}
				$description .= "*****************************************************\n";
				$description .= "Site Priority: " . $location->getPriorityString() . "\n";
				$opengear = $location->getOpengear();
				$description .= "*****************************************************\n";
				if($opengear)
				{
					$description .= "Opengear " . strtoupper($location->name) . "OOB01 status: " . $opengear . "\n";
				} else {
					$description .= "Opengear " . strtoupper($location->name) . "OOB01 does NOT exist!\n";
				}
				$weatherdesc = $location->getWeather();
				if($weatherdesc)
				{
				$description .= "*****************************************************\n";
				$description .= "Weather Information : " . $weatherdesc . "\n";
				}
				return $description;
			}
		}
		return "";
	}
	
	public function createTicketDescription()
	{
		$description = "";

		$description .= "The following ALERTS have been received: \n\n";
		$description .= $this->getStateStatus();
		$description .= $this->createLocationDescription();
		$description .= "*****************************************************\n";
		return $description;
	}

	public function createTicket()
	{
		$urgency = $this->getUrgency();
		$impact = $this->getImpact();
		if($this->type == "company")
		{
			$summary = "ALERTS have been received for more than " . env("COMPANY_OUTAGE_COUNT") . " locations!";
		}
		if($this->type == "site")
		{
			$summary = "ALERTS have been received for MULTIPLE devices at site " . strtoupper($this->name);
		}
		if($this->type == "device")
		{
			$summary = "ALERTS have been received for device " . strtoupper($this->name);
		}	
	
		$description = $this->createTicketDescription();
		print "Creating Ticket of type " . $this->type . "\n";
		$ticket = ServiceNowIncident::create([
			"cmdb_ci"			=>	env('SNOW_cmdb_ci'),
			"impact"			=>	$impact,
			"urgency"			=>	$urgency,
			"short_description"	=>	$summary,
			"description"		=>	$description,
			"assigned_to"		=>	"",
			"caller_id"			=>	env('SNOW_caller_id'),
			"assignment_group"	=>	env('SNOW_assignment_group'),
		]);
		if($ticket)
		{
			$this->ticket = $ticket->sys_id;
			$this->last_opened = Carbon::now();
			$this->save();
			if($urgency == 1)
			{
				if(Utility::isAfterHours())
				{
					$msg = "A " . $ticket->getPriorityString() . " priority incident has been opened." . Troppo::stringToVoice($ticket->number) . ", Multiple devices are down at site " . Troppo::stringToVoice($this->name);
					$this->callOncall($msg);
				}
			}
			return $ticket;
		}
		return null;
	}

	public function reopenTicket()
	{
		$ticket = $this->get_ticket();
		$unstates = $this->get_unresolved_states();
		if($ticket)
		{
			$msg = "The following ALERTS have been received: \n";
			$msg .= $this->getStateStatus();
			$msg .= "\nReopening the ticket!";
			$ticket->add_comment($msg);
			$this->resolved = 0;
			$this->last_opened = Carbon::now();
			$this->called_oncall = null;
			$this->called_sup = null;
			$this->save();
			$ticket->urgency = $this->getUrgency();
			$ticket->impact = 2;
			//$ticket->assigned_to = "";
			$ticket->state=2;
			$ticket->save();
			if(Utility::isAfterHours())
			{
				if($ticket->priority == 1 || $ticket->priority == 2)
				{
					$msg = "A " . $ticket->getPriorityString() . " priority incident has been reopened.  Ticket Number " . Troppo::stringToVoice($ticket->number) . "," . $ticket->short_description . ", Site Code " . Troppo::stringToVoice($this->name);
					$this->callOncall($msg);
				}
			}
			return true;
		}
		return false;
	}
	
	public function autoCloseTicket()
	{
		$ticket = $this->get_ticket();
		$msg = "All devices have recovered.  Auto Closing Ticket!";
		print $this->name . " " . $msg . "\n";
		$ticket->add_comment($msg);
		print "CLOSE TICKET : " . $this->name . "\n";
		$ticket->close($msg);
		$this->close();
	}
	
	public function get_states()
	{
		return State::where('incident_id', $this->id)->get();
	}
	
	public function getUniqueDeviceStates()
	{
		$states = $this->get_states();
		return $states->groupBy('name');
	}
	
	public function get_latest_state()
	{
		$states = $this->get_states();
		$neweststate = $states->first();
		foreach($states as $state)
		{
			if($state->updated_at->gt($neweststate->updated_at))
			{
				$neweststate = $state;
			}
		}
		return $neweststate;
	}
	
	public function updateTicket()
	{
		$msg = "";
		if($ticket = $this->get_ticket())
		{
			$msg.= "State update detected.  Current status:\n";
			$msg .= $this->getStateStatus();
			$ticket->add_comment($msg);
			return 1;
		}
		return null;
	}

	public function callOncall($msg)
	{
		$call = Troppo::callVoice(env("TROPO_ONCALL_NUMBER"),$msg);
		if($call)
		{
			$ticket = $this->get_ticket();
			if($ticket)
			{
				$comment = "Called network oncall at " . env("TROPO_ONCALL_NUMBER") . " and played the following message : \n";
				$comment .= $msg;
				$ticket->add_comment($comment);
			}
		} 
		return $call;
	}

	public function escalateOncall($msg)
	{
		$call = Troppo::callVoice(env("TROPO_ONCALL_NUMBER"),$msg);
		if($call)
		{
			$this->called_oncall = Carbon::now();
			$this->save();
			$ticket = $this->get_ticket();
			if($ticket)
			{
				$comment = "Called network oncall at " . env("TROPO_ONCALL_NUMBER") . " and played the following message : \n";
				$comment .= $msg;
				$ticket->add_comment($comment);
			}
		}
		return $call;
	}

	public function escalateSup($msg)
	{
		$call = Troppo::callVoice(env("TROPO_SUP_NUMBER"),$msg);
		if($call)
		{
			$this->called_sup = Carbon::now();
			$this->save();
			$ticket = $this->get_ticket();
			if($ticket)
			{
				$comment = "Called network supervisor at " . env("TROPO_SUP_NUMBER") . " and played the following message : \n";
				$comment .= $msg;
				$ticket->add_comment($comment);
			}
		}
		return $call;
	}

	public function get_unresolved_states()
	{
		return State::where('incident_id', $this->id)->where("resolved",0)->get();
	}
	
	public function get_resolved_states()
	{
		return State::where('incident_id', $this->id)->where("resolved",1)->get();
	}
	
	public function get_unprocessed_states()
	{
		return State::where('incident_id', $this->id)->where("processed",0)->get();
	}
	
	public function get_ticket()
	{
		if ($this->ticket)
		{
			if($ticket = ServiceNowIncident::where("sys_id","=",$this->ticket)->first())
			{
				return $ticket;
			}
		}
		return null;
	}

	public function process()
	{
		//Fetch me our ticket
		$ticket = $this->get_ticket();
		$unstates = $this->get_unresolved_states();
		$unpstates = $this->get_unprocessed_states();
		
		if($ticket)
		{
			//if the service now ticket is CLOSED (not resolved, but completely closed or cancelled)
			if($ticket->state == 7 || $ticket->state == 4)
			{
				//Purge this incident and all related states.
				$this->purge();
			//If the SNOW ticket is in RESOLVED state
			} elseif ($ticket->state == 6) {
				//IF INCIDENT IS NOT RESOLVED
				if($this->isOpen())
				{
					$msg = "Manual ticket closure was detected.  Clearing " . $this->name . " from Netaas system.\n";
					$msg .= "Current status of States: \n";
					$msg .= $this->getStateStatus();
					//ADD COMMENT TO TICKET
					$ticket->add_comment($msg);
					//Set incident to RESOLVED
					if($this->type == "company")
					{
						//Purge all states attached to this incident and the incident.
						$this->purge();
					} else {
						//Purge all states attached to this incident.
						$this->purgeStates();
						$this->close();
					}
				//IF INCIDENT IS RESOLVED
				} else {
					//If there are unresolved states, reopen ticket
					if($unstates->isNotEmpty())
					{
						$this->reopenTicket();
					} elseif($this->updated_at->lt(Carbon::now()->subHours(env('TIMER_AUTO_RELEASE_TICKET')))) {
						$ticket->add_comment("This ticket has been in a resolved state for over " . env('TIMER_AUTO_RELEASE_TICKET') . " hours. This ticket is no longer tracked by the Netaas system.");
						$this->purge();
					}
				}
			//If the SNOW ticket is OPEN
			} else {
				//IF INCIDENT IS OPEN
				if($this->isOpen())
				{
					if($unpstates->isNotEmpty())
					{
						$this->updateTicket();
					}
					if($unstates->isEmpty())
					{
						if($this->get_latest_state()->updated_at->lt(Carbon::now()->subMinutes(env('TIMER_AUTO_RESOLVE_TICKET'))))
						{
							$this->autoCloseTicket();
						}
					}
					if(Utility::isAfterHours())
					{
						if($ticket->priority == 1 || $ticket->priority == 2)
						{
							if($this->last_opened->lt(Carbon::now()->subMinutes(env("TROPO_UNASSIGNED_SUP_ALERT_DELAY"))) && !$ticket->assigned_to && !$this->called_sup)
							{
								$msg = "A " . $ticket->getPriorityString() . " priority incident has been opened for more than " . env("TROPO_UNASSIGNED_SUP_ALERT_DELAY") . " minutes and is currently not assigned.  Ticket Number " . Troppo::stringToVoice($ticket->number) . "," . $ticket->short_description . ", Site Code " . Troppo::stringToVoice($this->name);
								$this->escalateSup($msg);
							}
							if($this->last_opened->lt(Carbon::now()->subMinutes(env("TROPO_UNASSIGNED_ONCALL_ALERT_DELAY"))) && !$ticket->assigned_to && !$this->called_oncall) {
								$msg = "A " . $ticket->getPriorityString() . " priority incident has been opened for more than " . env("TROPO_UNASSIGNED_ONCALL_ALERT_DELAY") . " minutes and is currently not assigned.  Ticket Number " . Troppo::stringToVoice($ticket->number) . "," . $ticket->short_description . ", Site Code " . Troppo::stringToVoice($this->name);
								$this->escalateOncall($msg);
							}
						}
					}
				//IF INCIDENT IS CLOSED
				} else {
					if($unstates->isEmpty())
					{
						$msg = "Ticket was manually re-opened.  Currently there are NO devices in an ALERT state.";
					} else {
						$msg = "Ticket was manually re-opened.  The following are currently in an ALERT state: \n";
						$msg .= $this->getStateStatus();
					}
					$ticket->add_comment($msg);
					$this->resolved = 0;
					$this->save();
				}
			}
		//IF THERE IS NO SNOW TICKET
		} else {
			//IF TYPE IS SITE OR THERE ARE UNRESOLVED STATES
			if($this->type == "site" || $unstates->isNotEmpty())
			{
				//Create a new snow ticket
				print $this->name . " Creating a SNOW ticket!\n";
				$this->createTicket();
			}
		}
	}
}
