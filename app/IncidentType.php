<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncidentType extends Model
{
	use SoftDeletes;

	public static function getIncidentTypeByName($name)
	{
		return IncidentType::where("name",$name)->first();
	}
}
