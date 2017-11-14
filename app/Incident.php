<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Ticket;
use App\State;
use App\ServiceNowIncident;
use App\ServiceNowLocation;
use Carbon\Carbon;

class Incident extends Model
{
	use SoftDeletes;
	protected $fillable = ['name','type'];

	//close this incident
	public function close()
	{
		$this->resolved = 1;
		$this->save(); 
	}

	//open this incident
	public function open()
	{
		$this->resolved = 0;
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
		if ($location)
		{
			return $location;
		} else {
			return null;
		}
	}

	public function create_ticket_description()
	{
		$description = "";
		if($this->type == "site")
		{
			$description .= "The following devices are in an ALERT state at site " . strtoupper($this->name) . ": \n";
			foreach($this->get_states() as $state)
			{
				$description .= $state->name . "\n";
			}
		}
		if($this->type == "device")
		{
			$description .= "The following device is in an ALERT state : \n";
			$description .= strtoupper($this->name) . "\n";
		}

		$description .= "\n";
		$location = $this->get_location();
		if ($location)
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
				$description .= "Site Contact: \nName: " . $contact->name . "\nPhone: " . $contact->phone . "\n";			
			}
			$description .= "*****************************************************\n";
			if($location->u_priority == 0)
			{
				$description .= "Site Priority: NO MONITORING!\n";
			} elseif ($location->u_priority == 1) {
				$description .= "Site Priority: NEXT BUSINESS DAY\n";
			} elseif ($location->u_priority == 2) {
				$description .= "Site Priority: 24/7\n";
			}
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
		} else {
			$description .= 'Location "' . strtoupper(substr($this->name,0,8)) . '" not found!';
		}
		$description .= "*****************************************************\n";
		return $description;
	}

	public function create_ticket()
	{
		$description = $this->create_ticket_description();
		if($this->type == "site")
		{
			print "Creating Ticket of type site\n";
			$ticket = ServiceNowIncident::create([
				"cmdb_ci"			=>	env('SNOW_cmdb_ci'),
				"impact"			=>	env('SNOW_SITE_IMPACT'),
				"urgency"			=>	env('SNOW_SITE_URGENCY'),
				"short_description"	=>	"Multiple devices down at site " . strtoupper($this->name),
				"description"		=>	$description,
				"assigned_to"		=>	"",
				"caller_id"			=>	env('SNOW_caller_id'),
				"assignment_group"	=>	env('SNOW_assignment_group'),
			]);
		} else {
			print "Creating Ticket of type device\n";
			$ticket = ServiceNowIncident::create([
				"cmdb_ci"			=>	env('SNOW_cmdb_ci'),
				"impact"			=>	env('SNOW_DEVICE_IMPACT'),
				"urgency"			=>	env('SNOW_DEVICE_URGENCY'),
				"short_description"	=>	"Device " . strtoupper($this->name) . " is down!",
				"description"		=>	$description,
				"assigned_to"		=>	"",
				"caller_id"			=>	env('SNOW_caller_id'),
				"assignment_group"	=>	env('SNOW_assignment_group'),
			]);
		}
		//print_r($ticket);
		//print $ticket->sysid . "\n";
		$this->ticket = $ticket->sys_id;
		$this->save();
		return $ticket;
	}

	public function get_states()
	{
		return State::where('incident_id', $this->id)->get();
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
	
	public function get_unresolved_states()
	{
		return State::where('incident_id', $this->id)->where("resolved",0)->get();
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

	public function checkTickets()
	{
		//Fetch me our ticket
		$ticket = $this->get_ticket();
		$states = $this->get_states();
		$unstates = $this->get_unresolved_states();
		
		if($ticket)
		{
			//if the service now ticket is CLOSED (not resolved, but completely closed or cancelled)
			if($ticket->state == 7 || $ticket->state == 4)
			{
				//$ticket->add_comment("Service Now Ticket has been closed prior to all devices recovering.  Clearing " . $this->name . " from Netaas system.");
				//Delete all states associated to this internal incident
				foreach($states as $state)
				{
					$state->delete();
				}
				//DELETE this internal incident from database.
				$this->delete();
			//If the SNOW ticket is in RESOLVED state
			} elseif ($ticket->state == 6) {
				//IF INCIDENT IS NOT RESOLVED
				if($this->isOpen())
				{
					if($unstates->isEmpty())
					{
						//ALL STATES ARE RESOLVED AND TICKET WAS MANUALLY CLOSED
						$msg = "Manual ticket closure was detected, but all states are resolved anyways.  Clearing " . $this->name . " from Netaas system.\n";
					} else {
						//ALL STATES ARE NOT RESOLVED AND TICKET WAS MANUALLY CLOSED
						$msg = "Manual ticket closure was detected, but all states were NOT resolved.  Clearing " . $this->name . " from Netaas system.\n";
					}
					$msg .= "The following STATES have been removed from the Netaas system: \n";

					//DELETE ALL STATES for this incident
					foreach($states as $state)
					{
						$msg .= $state->name . "\n";
						$state->delete();
					}
					//ADD COMMENT TO TICKET
					$ticket->add_comment($msg);
					//Set incident to RESOLVED
					$this->close();
				//IF INCIDENT IS RESOLVED
				} else {
					//If there are unresolved states
					if($unstates->isNotEmpty())
					{
						//COMMENT SNOW TICKET
						$msg = "The following devices have entered an alert state: \n";
						//REOPEN INCIDENT AND SNOW TICKET
						foreach($unstates as $unstate)
						{
							$msg .= $unstate->name . "\n";
						}
						$msg .= "\nReopening the ticket!";
						$ticket->add_comment($msg);
						$this->open();
						$ticket->open();
					}
				}
			//If the SNOW ticket is OPEN
			} else {
				//IF INCIDENT IS OPEN
				if($this->isOpen())
				{
					if($unstates->isEmpty())
					{
						if($this->get_latest_state()->updated_at->lt(Carbon::now()->subMinutes(env('TIMER_AUTO_RESOLVE_TICKET'))))
						{
							$msg = "All devices have recovered.  Auto Closing Ticket!";
							print $this->name . " " . $msg . "\n";
							$ticket->add_comment($msg);
							print "CLOSE TICKET : " . $this->name . "\n";
							$ticket->close($msg);
							$this->close();
						}
					}
				//IF INCIDENT IS CLOSED
				} else {
					if($unstates->isEmpty())
					{
						$msg = "Ticket was manually re-opened.  Currently there are NO devices in an ALERT state.";
					} else {
						$msg = "Ticket was manually re-opened.  The following devices are currently in an ALERT state: \n";
						foreach($unstates as $unstate)
						{
							$msg .= $unstate->name . "\n";
						}
					}
					$ticket->add_comment($msg);
					$this->open();
				}
			}
		//IF THERE IS NO SNOW TICKET
		} else {
			//IF TYPE IS SITE OR THERE ARE UNRESOLVED STATES
			if($this->type == "site" || $unstates->isNotEmpty())
			{
				//Create a new snow ticket
				print $this->name . " Create SNOW ticket!\n";
				$this->create_ticket();
			}
		}
	}

	public function process()
	{
		print "Processing Incident " . $this->name . "!!\n";
		$this->checkTickets();
	}
}
