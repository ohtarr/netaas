<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Ticket;
use App\State;
use App\ServiceNowIncident;
use App\ServiceNowLocation;
use App\ServiceNowServer;
use Carbon\Carbon;
use GuzzleHttp\Client as GuzzleHttpClient;
use App\IncidentType;

class Incident extends Model
{
	use SoftDeletes;
	protected $fillable = ['name','type_id'];

	protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
	];
	
	public $location = null;

	public function states()
	{
		return $this->hasMany('App\State');
	}

	public function incidentType()
	{
        return $this->hasOne('App\IncidentType','id','type_id');		
	}

	public function ticket()
    {
		if(!$this->ticket)
		{
			return null;
		}
		return ServiceNowIncident::find($this->ticket_id);
	}
	
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
/*
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
/**/	
	public function get_location()
	{
		if(!$this->location)
		{
			//grab the first 8 characters of our name.  This is our sitecode!
			$sitecode = strtoupper(substr($this->name,0,8));
			$location = null;
			try
			{
				$location = ServiceNowLocation::where("name","=",$sitecode)->first();
			} catch(\Exception $e) {
			
			}
			$this->location = $location;
		}
		return $this->location;
	}
/*
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
					$description .= "ALERT\t";
				} else {
					$description .= "RECOVER\t";
				}
				$description .= $state->entity_desc . "\n";
			}
			$description .= "\n";
		}
		return $description;
	}
/**/	
	public function compileString($string)
	{
		$server = ServiceNowServer::where('name',$this->name)->first();
		if($server)
		{
			$server_desc = $server->short_description;
		} else {
			$server_desc = "NO VALID SERVER";
		}
		$location = $this->get_location();
		if($location)
		{
			$contact = $location->getContact();
			if($contact)
			{
				$contactdesc = "Site Contact: \nName: " . $contact->name . "\nPhone: " . $contact->phone . "\nMobile: " . $contact->mobile_phone . "\nEmail: " . $contact->email . "\n";
			} else {
				$contactdesc = "NO VALID CONTACT FOUND";
			}
			$weather = $location->getWeather();
			$opengear = $location->getOpengear();
			if(!$opengear)
			{
				$opengear = "NO OPENGEAR FOUND";
			}
			$locdesc = "";
			$locdesc .= "SITE NAME: " . $location->name . "\n\n";
			$locdesc .= "Display Name: " . $location->u_display_name . "\n\n";
			$locdesc .= "Description: " . $location->description . "\n\n";
			$locdesc .= "Address: " . $location->street . ", " . $location->city . ", " . $location->state . ", " . $location->zip . "\n\n";
			$locdesc .= "Comments: \n" . $location->u_comments . "\n";
			$priority = $location->getPriorityString();
		} else {
			$contactdesc = "NO VALID CONTACT";
			$weather = "NO VALID WEATHER INFORMATION";
			$opengear = "NO VALID OPENGEAR";
			$locdesc = "NO VALID LOCATION";
			$priority = "NO VALID PRIORITY";
		}
		$result = $string;
		$result = preg_replace('/{{name}}/', $this->name, $result);
		$result = preg_replace('/{{state_summary}}/', $this->compileStateSummary(), $result);
		$result = preg_replace('/{{count_states}}/', $this->get_states()->count(), $result);
		$result = preg_replace('/{{count_devices}}/', $this->getUniqueDeviceStates()->count(), $result);
		$result = preg_replace('/{{contact}}/', $contactdesc, $result);
		$result = preg_replace('/{{weather}}/', $weather, $result);
		$result = preg_replace('/{{opengear}}/', $opengear, $result);
		$result = preg_replace('/{{location}}/', $locdesc, $result);
		$result = preg_replace('/{{priority}}/', $priority, $result);
		$result = preg_replace('/{{timestamp}}/', Carbon::now()->toDateTimeString(), $result);
		//$result = preg_replace('/{{incident_type}/', IncidentType::find($this->incident_type_id)->name, $result);
		$result = preg_replace('/{{company_threshold}}/', env('COMPANY_OUTAGE_COUNT'), $result);
		$result = preg_replace('/{{server_desc}}/', $server_desc, $result);
		return $result;
	}

 	public function compileStateSummary()
	{
		$description = "";
		foreach($this->getUniqueDeviceStates() as $name => $device)
		{
			$description .= "[" . $name . "]\n";
			foreach($device as $state)
			{
				if($state->resolved == 0)
				{
					$description .= "---[ALERT]";
				} else {
					$description .= "+++[RECOVER]";
				}
				$description .= " [" . Carbon::parse($state->updated_at)->Format('m/d g:i A') . "]";
				$description .= " [" . $state->type . "]";
				$description .= " [" . $state->entity_type . "]";
				if($state->entity_name)
				{
					$description .= " [" . $state->entity_name . "]";
				}
				if($state->entity_desc)
				{
					if(is_array(json_decode($state->entity_desc,TRUE))){
						$description .= "\n";
						$description .= "DESCRIPTION:\n";
						$description .= json_encode(json_decode($state->entity_desc),JSON_PRETTY_PRINT);
					} else {
						$description .= " [" . $state->entity_desc . "]";
					}
				}
				$description .= "\n";
			}
			//$description .= "\n";
		}
		return $description;
	}

	/* public function compileStateSummary()
	{
		$description = "";
		foreach($this->getUniqueDeviceStates() as $name => $device)
		{
			$description .= "### DEVICE " . $name . " ###\n";
			foreach($device as $state)
			{
				if($state->resolved == 0)
				{
					$description .= "  [ALERT]\t";
				} else {
					$description .= "  [RECOVER]\t";
				}
				$description .= "" . Carbon::parse($state->updated_at)->Format('m/d g:i A') . "\t";
				$description .= "" . $state->type . "\t";
				$description .= "" . $state->entity_type . "\t";
				if($state->entity_name)
				{
					$description .= "" . $state->entity_name . "\t";
				}
				if($state->entity_desc)
				{
					$description .= "" . $state->entity_desc . "\t";
				}
				$description .= "\n";
			}
			$description .= "\n";
		}
		return $description;
	} */

/*
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
        if($this->type == "company")
        {
            $description .= "This is considered a COMPANY-WIDE outage due to more than " . env("COMPANY_OUTAGE_COUNT") . " SITES in an alert state in a short timeframe.  Any and all additional alerts that occur will be added to this incident until it is marked resolved.  Please determine the cause or possible cause of these sites alerting, resolve the issues, and mark this incident resolved as soon as possible.\n\n";
			$description .= "After this incident is marked resolved, please review the final comment indicating what is still DOWN and manually create any incidents as needed to resolve those issues.\n";
			$description .= "\n";
        }
		$description .= "The following STATES have generated alerts.  The UP/DOWN status below indicates the states status at the time of this ticket being created. \n\n";
		$description .= $this->compileStateSummary();
		$description .= $this->createLocationDescription();
		$description .= "*****************************************************\n";
		return $description;
	}
/**/
	public function createTicket()
	{
		$location = $this->get_location();
		if($location)
		{
			if($location->u_active == "true" || $location->u_priority == 0)
			{
				print "Location is deactivated or set to NO MONITORING, purging from system\n";
				$this->purge();
				return null;
			}
		}

		print "Creating Ticket of type " . $this->IncidentType->name . "\n";
		$ticket = ServiceNowIncident::create([
			"cmdb_ci"			=>	$this->IncidentType->ci_id,
			"impact"			=>	$this->IncidentType->impact,
			"urgency"			=>	$this->IncidentType->urgency,
			"short_description"	=>	$this->compileString($this->IncidentType->summary),
			"description"		=>	$this->compileString($this->IncidentType->description),
			"assigned_to"		=>	"",
			"caller_id"			=>	$this->IncidentType->caller_id,
			"assignment_group"	=>	$this->IncidentType->group_id,
		]);
		if($ticket)
		{
			$this->ticket_id = $ticket->sys_id;
			$this->save();
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
			$msg .= $this->compileStateSummary();
			$msg .= "\nReopening the ticket!";
			$ticket->add_comment($msg);
			$this->resolved = 0;
			$this->save();
			$ticket->urgency = $this->IncidentType->urgency;
			$ticket->impact = $this->IncidentType->impact;
			//$ticket->assigned_to = "";
			$ticket->state=2;
			$ticket->save();
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
		return $states->groupBy('device_name');
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
		$ticket = $this->get_ticket();
		if($ticket)
		{
			$msg.= "State update detected.  Current status:\n";
			$msg .= $this->compileStateSummary();
			$ticket->add_comment($msg);
			return 1;
		}
		return null;
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
		if(!$this->ticket_id)
		{
			return null;
		}
		if($ticket = ServiceNowIncident::where("sys_id","=",$this->ticket_id)->first())
		{
			return $ticket;
		}
	}

	/*
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
			//IF TYPE IS SITE OR COMPANY OR THERE ARE UNRESOLVED STATES
			if($this->type == "site" || $this->type == "company" || $unstates->isNotEmpty())
			{
				//Create a new snow ticket
				print $this->name . " Creating a SNOW ticket!\n";
				$this->createTicket();
			}
		}
	}
	/**/
	public static function getIncidentsOfType($type)
	{
		$IncidentType = IncidentType::where('name',$type)->first();
		return Incident::where('type_id',$IncidentType->id)->get();
	}
}
