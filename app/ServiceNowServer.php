<?php

namespace App;

use ohtarr\ServiceNowModel;

class ServiceNowServer extends ServiceNowModel
{
	protected $guarded = [];

	public $table = "cmdb_ci_server";

}
