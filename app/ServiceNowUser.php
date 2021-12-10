<?php

namespace App;

use ohtarr\ServiceNowModel;

class ServiceNowUser extends ServiceNowModel
{
	protected $guarded = [];

	public $table = "sys_user";
	
	public function __construct(array $attributes = [])
	{
			$this->snowbaseurl = env('SNOW_API_URL');
			$this->snowusername = env("SNOW_USERNAME");
			$this->snowpassword = env("SNOW_PASSWORD");
			parent::__construct($attributes);
	}
}