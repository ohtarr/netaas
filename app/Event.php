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
		return State::where('name', $this->name)->where('type', $this->message)->where('entity_name', $this->entity_name)->first();
	}

	public function create_state()
	{
		return State::create([
			'name'					=>	$this->name,
			'type'					=>	$this->message,
			'resolved'				=>	$this->resolved,
			'processed'				=>	0,
			'entity_name'			=>	$this->entity_name,
			'entity_desc'			=>	$this->entity_desc,
		]);
	}

}
