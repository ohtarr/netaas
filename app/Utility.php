<?php

namespace App;

use Carbon\Carbon;

class Utility
{
	public static function isAfterHours()
	{
		$start = Carbon::createFromTime(env("TIME_WORKDAY_START"),0,0,env("TIME_ZONE"));
		$end = Carbon::createFromTime(env("TIME_WORKDAY_END"),0,0,env("TIME_ZONE")); 
		$now = Carbon::now(env("TIME_ZONE"));
		if($now->isWeekday())
		{
			if($now->between($start, $end))
			{
				return false;
			}
		}
		return true;
	}
}
