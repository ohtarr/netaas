<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use GuzzleHttp\Client as GuzzleHttpClient;
use App\ServiceNowUser as ServiceNowUser;

class ServiceNowLocation extends Model
{
	protected $guarded = [];

	public $table = "cmn_location";

	public function newQuery()
	{
		$builder = new ServiceNowQueryBuilder();
		$builder->setModel($this);
		return $builder;
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

	}

	//Create a new Snow Ticket
	public static function create($attribs = [])
	{
	
	}
	
	public function getContact()
	{
		if(isset($this->contact["value"]))
		{
			$user = $this->contact['value'];
			return ServiceNowUser::find($user);
		}
		return null;
	}
}