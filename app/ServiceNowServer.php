<?php

namespace App;

use ohtarr\ServiceNowModel;

class ServiceNowServer extends ServiceNowModel
{
	protected $guarded = [];

	public $table = "cmdb_ci_server";

	public function __construct(array $attributes = [])
	{
			$this->snowbaseurl = env('SNOW_API_URL');
			$this->snowusername = env("SNOW_USERNAME");
			$this->snowpassword = env("SNOW_PASSWORD");
			parent::__construct($attributes);
	}
}
