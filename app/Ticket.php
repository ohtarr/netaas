<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Incident;
use App\State;
use Carbon\Carbon;

class Ticket extends Model
{
	use SoftDeletes;
	protected $fillable = ['sysid','type','title','description','level','resolved','options'];
	
	public function add_comment($comment)
	{
	
	}
/*
	public function create()
	{
		//code here to create a ticket
		
	}
/**/
/*
	public function save()
	{
		//code here to save a ticket
	}
/**/
	public function close()
	{
		//code here to close a ticket
		$this->resolved = 1;
		$this->save();
	}

	public function open()
	{
		//code here to open a ticket
		$this->resolved = 0;
		$this->save();
	}

	public function get_incident()
	{
		return Incident::where('ticket', $this->sysid)->first();
	}
	
	public function get_states()
	{
		$incident = $this->get_incident();
		return State::where('incident_id', $incident->id)->get();
	}
	
	public function update_incident_status()
	{
		$incident = $this->get_incident();
		$states = $this->get_states();
		if($this->resolved == 1 && $incident->resolved == 0)
		{
			foreach($states as $state)
			{
				$state->delete();
			}
			$incident->resolved = 1;
			$incident->save();
		}
		//return $incident;
	}

	public function auto_close()
	{
		//if($this->resolved == 1 && $this->updated_at < Carbon::now()->subDays(2))
		if($this->resolved == 1 && Carbon::parse($this->updated_at)->lt(Carbon::now()->subHours(1)))		
		{
			$incident = $this->get_incident();
			$states = $this->get_states();
			$incident->delete();
			foreach($states as $state)
			{
				$state->delete();
			}
			$this->delete();
		}
	}

	public function process()
	{
		$this->update_incident_status();
		$this->auto_close();
	}

}
