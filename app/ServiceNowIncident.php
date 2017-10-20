<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use GuzzleHttp\Client as GuzzleHttpClient;

class ServiceNowIncident extends Model
{
	protected $guarded = [];

	public $table = "incident";

	public function newQuery()
	{
		$builder = new ServiceNowQueryBuilder();
		$builder->setModel($this);
		return $builder;
	}

	//get all records created by this system
    public static function all_mine($columns = ['*'])
    {
		$instance = new static;
		return $instance->newQuery()
						->where('sys_created_by', '=', env('SNOW_USERNAME'))
						->get();
    }

	//get all table records
    public static function all($columns = ['*'])
    {
		$instance = new static;
		return $instance->newQuery()
						->get();
    }

	//Find a snow ticket via sysid
	public static function find($sysid)
	{
		$instance = new static;
		return $instance->newQuery()
						->where('sys_id',"=", $sysid)
						->get()
						->first();
	}
	//Update a snow ticket
	public function save(array $options = [])
	{
		if($this->sys_id)
		{
			//return $this->newQuery()->put();
			$return = $this->newQuery()->put();

		} else {
			//return $this->newQuery()->post();
			$return = $this->newQuery()->post();
		}
		$this->fill($return->toArray());
		$this->syncOriginal();
		return $this;
		//return $return;
	}
	//Create a new Snow Ticket
	public static function create($attribs = [])
	{
		$instance = new static($attribs);
		//return $instance->newQuery()->post();
		return $instance->save();
	}
	
	public function close()
	{
		$this->state = 6;
		$this->save();
	}

	public function open()
	{
		$this->state = 2;
		$this->save();
	}

	public function add_comment($comment)
	{
		$this->comments = $comment;
		$this->save();
		//return $this;
	}
	
	public function get_incident()
	{
		return Incident::where('ticket', $this->sys_id)->first();
	}
	
	public function isOpen()
	{
		if ($this->state == 4 || $this->state == 6 || $this->state == 7)
		{
			return false;
		} else {
			return true;
		}
	}
	
	/*
	public function get_states()
	{
		$incident = $this->get_incident();
		return $incident->get_states();
	}
	
	public function update_incident_status()
	{
		$incident = $this->get_incident();
		$states = $this->get_states();
		if(!$this->isOpen() && $incident->resolved == 0)
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

	public function process()
	{
		//$this->update_incident_status();
	}
/**/	

}