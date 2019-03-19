<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\State;
use Carbon\Carbon;

class Event extends Model
{
	use SoftDeletes;
	
	public function get_state()
	{
		return State::where('device_name', $this->device_name)->where('type', $this->type)->where('entity_type', $this->entity_type)->where('entity_name', $this->entity_name)->first();
	}

	public function update_state()
	{
		$state = $this->get_state();
		$state->resolved = $this->resolved;
		$state->processed = 0;
		$state->save();
		return $state;
	}

 	public function create_state()
	{
		return State::create([
			'device_name'			=>	$this->device_name,
			'type'					=>	$this->type,
			'resolved'				=>	$this->resolved,
			'processed'				=>	0,
			'entity_type'			=>	$this->entity_type,
			'entity_name'			=>	$this->entity_name,
			'entity_desc'			=>	$this->entity_desc,
		]);
	}

	public function mark_processed()
	{
		$this->processed = 1;
		$this->save();
		return $this;
	}

	public static function getEventsBetween($start, $end)
	{
		$start = new Carbon($start);
		$start->tz = 'America/Chicago';
		$end = new Carbon($end);
		$end->tz = 'America/Chicago';
		return Event::withTrashed()->whereBetween('created_at', array($start, $end))->get();
	}

	public static function getEventsLastDays($days)
	{
		return Event::withTrashed()->where('created_at', ">", Carbon::now()->subDays($days))->get();
	}

	public static function getYesterday()
	{
		$start = Carbon::Yesterday();
		$end = Carbon::Today(); 
		return self::getEventsBetween($start, $end);
	}

}
