<?php

namespace App\Http\Controllers;

use App\Event;
use Illuminate\Http\Request;

class EventsController extends Controller
{

	public function getEvents()
	{
		return Event::all();
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
