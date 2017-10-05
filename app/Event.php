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
		return State::where('name', $this->name)->first();
	}

	public function create_state()
	{
		return State::create([
			'name'		=>	$this->name,
			'type'		=>	$this->type,
			'resolved'	=>	$this->resolved,
			'processed'	=>	0,
		]);
	}

	public function process()
	{
		print "Processing event for device " . $this->name . "...\n";
		$state = $this->get_state();
		if($state)
		{
			$state->resolved = $this->resolved;
			$state->processed = 0;
			$state->save();
		} else {
			$state = $this->create_state();
		}
		if($state)
		{
			$this->processed = 1;
			$this->save();
		}
	}
}
