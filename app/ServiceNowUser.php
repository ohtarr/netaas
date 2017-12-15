<?php

namespace App;

use ohtarr\ServiceNowModel;

class ServiceNowUser extends ServiceNowModel
{
	protected $guarded = [];

	public $table = "sys_user";
	
}