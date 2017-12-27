<?php

namespace App\Http\Controllers;

use App\Event;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EventsController extends Controller
{
	
	public function getEvents()
	{
		return Event::withTrashed()->get();
	}
	
	public static function getEventsBetween($start, $end)
	{
		$start = new Carbon($start);
		$start->tz = 'America/Chicago';
		$end = new Carbon($end);
		$end->tz = 'America/Chicago';
		return Event::withTrashed()->whereBetween('created_at', array($start, $end))->get();
	}

	public static function getEventsLastDays($days)
	{
		return Event::withTrashed()->where('created_at', ">", Carbon::now()->subDays($days))->get();
	}

	public static function getYesterday()
	{
		$start = Carbon::Yesterday();
		$end = Carbon::Today(); 
		return self::getEventsBetween($start, $end);
	}

	public function Netmon(Request $request)
	{
		$event = new Event;
		$event->src_ip = $request->ip();
		$event->src_type = "netmon";
		$event->name = $request['DEVICE_HOSTNAME'];

		$event->type = $request['ENTITY_TYPE'];
		if($request['ALERT_STATE'] == "ALERT")
		{
			$event->resolved = 0;		
		} else {
			$event->resolved = 1;
		}

		$event->title = $request['TITLE'];
		$event->message = $request['ALERT_MESSAGE'];
		$event->timestamp = $request['TIMESTAMP'];

		$event->entity_name = $request['ENTITY_NAME'];
		$event->entity_desc = $request['ENTITY_DESCRIPTION'];

		$array = [
		'ALERT_URL'			=>	$request['ALERT_URL'],
		'DURATION'			=>	$request['DURATION'],
		'DEVICE_LINK'		=>	$request['DEVICE_LINK'],
		'DEVICE_HARDWARE'	=>	$request['DEVICE_HARDWARE'],
		'DEVICE_OS'			=>	$request['DEVICE_OS'],
		'DEVICE_LOCATION'	=>	$request['DEVICE_LOCATION'],
		'DEVICE_UPTIME'		=>	$request['DEVICE_UPTIME'],
		'ENTITY_LINK'		=>	$request['ENTITY_LINK'],
		];

		$event->options = json_encode($array);

		$event->save();
	}
}
