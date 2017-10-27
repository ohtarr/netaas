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
		if ($this->resolved == 1)
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
				"impact"			=>	"2",
				"urgency"			=>	"1",
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
				"impact"			=>	"2",
				"urgency"			=>	"3",
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
	/*
	public function get_resolved_states()
	{
		return State::where('incident_id', $this->id)->where("resolved",1)->get();
	}
	/**/
	public function get_unresolved_states()
	{
		return State::where('incident_id', $this->id)->where("resolved",0)->get();
	}
	
	public function get_ticket()
	{
		if ($this->ticket)
		{
			return ServiceNowIncident::where("sys_id","=",$this->ticket)->first();
		}
		return null;
		
	}
	/*
	public function update_ticket_status()
	{
		$ticket = $this->get_ticket();
		if($ticket)
		{
			if($this->get_unresolved_states()->isEmpty())
			{
			
			}


			//If incident is open but snow ticket is closed
			if($this->isOpen() && !$ticket->isOpen())
			{
				//If incident hasn't been updated in last 30 minutes
				if ($this->updated_at < $this->updated_at->subMinutes(30))
				{
					$ticket->add_comment("");
					$ticket->open();
				}
			}

			if(!$this->isOpen() && $ticket->isOpen())
			{
				//If all incident states are resolved, mark this incident resolved.
				if($states->count() == $states->where('resolved', 1)->count())
				{
					$ticket->add_comment("All devices have been restored!");
					$this->close();
				}
				$ticket->close();
			}
		} else {
			$ticket = $this->create_ticket();
		}
		return $ticket;
	}
	/**/
	/*
	//Find an active ticket in Service Now and update our internal incident if needed.
	public function update_incident_status()
	{
		//Get TICKET
		$ticket = $this->get_ticket();
		//get all STATES for this incident
		$states = $this->get_states();
		//If ticket is resolved and incident is not,
		if(!$ticket->isOpen() && $this->isOpen())
		{
			$ticket->add_comment("All devices states have not cleared.  Closing due to Service-Now incident being closed anyways.");
			//Go through each state
			foreach($states as $state)
			{
				//Delete the state.
				$state->delete();
			}
			//set the incident to resolved and save.
			$this->close();
		}
		return $incident;
	}
	/**/
	public function checkStates()
	{
		//If there are NO unresolved states left
		if($this->get_unresolved_states()->isEmpty())
		{
			//RESOLVE this internal incident.
			if($this->isOpen())
			{
				print $this->name . " All states are RESOLVED.  Resolving this incident.\n";
				$this->close();
			}
		//If there are unresolved states left
		} else {
			//make sure this incident is open.
			if(!$this->isOpen())
			{
				print $this->name . " Incident is resolved, but we found some unresolved STATES.  Reopening incident.\n";
				$this->open();
			}
		}
	}

	public function checkIncidents()
	{
		if(!$this->ticket)
		{
			//If this internal incident is OPEN and there is no assigned SNOW ticket
			if($this->isOpen() || $this->type = "site")
			{
				//Create a new snow ticket
				print $this->name . " Create SNOW ticket!\n";
				$this->create_ticket();
			}
		} else {
			//If this incident is RESOLVED <AND> it hasn't been updated in the last 30 minutes
			if(!$this->isOpen() && $this->updated_at < Carbon::now()->subMinutes(env('TIMER_AUTO_RESOLVE_TICKET')))
			{
				//Comment on the ticket and mark it RESOLVED
				$ticket = $this->get_ticket();
				if($ticket->isOpen())
				{
					$msg = "All devices have recovered.  Auto Closing Ticket!";
					print $this->name . " " . $msg . "\n";
					$ticket->add_comment($msg);
					print "CLOSE TICKET : " . $this->name . "\n";
					$ticket->close($msg);
				}
			}
		}
	}
	
	public function checkTickets()
	{
		//Fetch me our ticket
		$ticket = $this->get_ticket();
		//If the TICKET is RESOLVED and this internal incident is still open:
		if($ticket->state == 6 && $this->isOpen())
		{
			print $this->name . " COMMENT: Service Now Ticket has been closed prior to all devices recovering.  Marking as resolved in Netaas system.\n"; 
			$ticket->add_comment("Service Now Ticket has been closed prior to all devices recovering.  Marking as resolved in Netaas system.");
			//Delete all states that are linked to this incident
			$states = $this->get_states();
			foreach($states as $state)
			{
				print "Delete STATE " . $state->name . "\n";
				$state->delete();
			}
			//resolve this incident
			print $this->name . ": Resolved this incident as the SNOW ticket was closed manually.\n";
			$this->close();
		}
		//if the service now ticket is CLOSED (not resolved, but completely closed)
		if($ticket->state == 7)
		{
			$ticket->add_comment("Service Now Ticket has been closed prior to all devices recovering.  Clearing " . $this->name . " from Netaas system.");
			//Delete all states associated to this internal incident
			$states = $this->get_states();
			foreach($states as $state)
			{
				$state->delete();
			}
			//DELETE this internal incident from database.
			$this->delete();
		}
	}

	public function process()
	{
		print "Processing Incident " . $this->name . "!!\n";
		$this->checkStates();
		$this->checkIncidents();
		$this->checkTickets();
			//$this->update_incident_status();
			//$this->update_ticket_status();
			//$this->create_ticket();
	}

}
