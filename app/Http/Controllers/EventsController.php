<?php

namespace App\Http\Controllers;

use App\Event;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EventsController extends Controller
{
	
	public function getEvents()
	{
		return Event::withTrashed()->get();
	}
	
/* 	public static function getEventsBetween($start, $end)
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
	} */

	public function Netmon(Request $request)
	{
		$message = "Received new request from " . $request->ip() . ":\n";
		$message .= $request;
		Log::info($message);
		$event = new Event;
		$event->src_ip = $request->ip();
		$event->device_name = $request['DEVICE_HOSTNAME'];
		$event->type = "NETWORK";
		if($request['ALERT_STATE'] == "ALERT")
		{
			$event->resolved = 0;		
		} else {
			$event->resolved = 1;
		}
		$event->entity_type = $request['ALERT_MESSAGE'];
		$event->entity_name = $request['ENTITY_NAME'];
		$event->entity_desc = $request['ENTITY_DESCRIPTION'];

		$event->save();
		return response()->json($event);
	}

	public function Scom(Request $request)
	{
		$message = "Received new request from " . $request->ip() . ":\n";
		$message .= $request;
		Log::info($message);
		$event = new Event;
		$event->src_ip = $request->ip();
		$event->device_name = $request['DEVICE_HOSTNAME'];
		if($request['TYPE'])
		{
			$event->type = $request['TYPE'];
		} else {
			$event->type = "SERVER";
		}
		if($request['ALERT_STATE'] == "0")
		{
				$event->resolved = 0;
		} else{
				$event->resolved = 1;
		}
		$event->entity_type = $request['ENTITY_TYPE'];
		$event->entity_name = $request['ENTITY_NAME'];
		$event->entity_desc = $request['ENTITY_DESCRIPTION'];
		$event->save();
		return $event;
	}

	public function Azure(Request $request)
	{
		$message = "Received new request from " . $request->ip() . ":\n";
		$message .= $request;
		Log::info($message);
		$event = new Event;
		$event->src_ip = $request->ip();
		$event->type = strtoupper($request['TYPE']);
		$event->device_name = $request['DEVICE'];
		$event->resolved = $request['RESOLVED'];
		$event->entity_type = $request['ENTITY_TYPE'];
		$event->entity_name = $request['ENTITY_NAME'];
		$event->entity_desc = $request['ENTITY_DESC'];
		$event->save();
		return $event;
	}

}
