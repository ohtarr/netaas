<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Ticket;
use App\State;
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

	public function create_ticket()
	{
		if($this->type == "site")
		{
			print "Creating Ticket of type site\n";
			$ticket = Ticket::create([
				'sysid'			=>	mt_rand(1000000000,9999999999),
				'type'			=>	"incident",
				'title'			=>	"MULTIPLE DEVICES DOWN AT SITE " . $this->name ,
				'description'	=>	"There are multiple devices down at site " . $this->name . ".  Please Fix!",
				'level'			=>	2,
			]);
		} else {
			print "Creating Ticket of type device\n";
			$ticket = Ticket::create([
				'sysid'			=>	mt_rand(1000000000,9999999999),
				'type'			=>	"incident",
				'title'			=>	"DEVICE DOWN: " . $this->name ,
				'description'	=>	"The following device is down: " . $this->name . ".  Please Fix!",
				'level'			=>	3,
			]);
		}
		//print_r($ticket);
		//print $ticket->sysid . "\n";
		$this->ticket = $ticket->sysid;
		$this->save();
	}

	public function get_states()
	{
		return State::where('incident_id', $this->id)->get();
	}

	public function get_ticket()
	{
		return Ticket::where('sysid',$this->ticket)->first();
	}

	public function update_ticket_status()
	{
		$ticket = $this->get_ticket();
		if($this->resolved == 0)
		{
			$time = $this->updated_at->subMinutes(30);
			if ($this->updated_at < $time)
			{
				$ticket->open();
			}
		}
		if($this->resolved == 1)
		{
			$ticket->close();
		}
		return $ticket;
	}

	public function update_incident_status()
	{
		//Get TICKET
		$ticket = $this->get_ticket();
		//get all STATES for this incident
		$states = $this->get_states();
		//If ticket is resolved and incident is not,
		if($ticket->resolved && !$this->resolved)
		{
			//Go through each state
			foreach($states as $state)
			{
				//Delete the state.
				$state->delete();
			}
			//set the incident to resolved and save.
			$this->close();
		}
		//If all incident states are resolved, mark this incident resolved.
		if($states->count() == $states->where('resolved', 1)->count())
		{
			$this->close();
		}
		//return $incident;
	}
	
	public function process()
	{
		print "Processing Incident " . $this->name . "!!\n";
		$ticket = $this->get_ticket();
		if($ticket)
		{
			$this->update_incident_status();
			$this->update_ticket_status();
		} else {
			$this->create_ticket();
		}
	}

}
