<?php

namespace App\Http\Controllers;

use App\Models\Event;
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
		$event->src_ip = substr($request->ip(),0,249);
		$event->device_name = substr($request['DEVICE_HOSTNAME'],0,249);
		$event->type = "NETWORK";
		if($request['ALERT_STATE'] == "ALERT")
		{
			$event->resolved = 0;
		} else {
			$event->resolved = 1;
		}
		$event->entity_type = substr($request['ALERT_MESSAGE'],0,249);
		$event->entity_name = substr($request['ENTITY_NAME'],0,249);
		$event->entity_desc = substr($request['ENTITY_DESCRIPTION'],0,9999);

		$event->save();
		return response()->json($event);
	}

	public function LibreNMS(Request $request)
	{
		$message = "Received new request from " . $request->ip() . ":\n";
		$message .= $request;
		Log::info($message);

		// LibreNMS sends pretty-printed JSON with structural newlines, which json_decode handles fine.
		// Read the raw body and decode directly. Only fall back to sanitizing if the initial decode
		// fails (e.g. literal unescaped newlines inside a string value).
		$raw = $request->getContent();
		$array = json_decode($raw, true);

		// Fallback: if raw decode fails, try sanitizing literal newlines inside string values
		if (json_last_error() !== JSON_ERROR_NONE) {
			$sanitized = preg_replace('/(?<!\\\\)\n/', '\\n', $raw);
			$array = json_decode($sanitized, true);
		}

		if (json_last_error() !== JSON_ERROR_NONE || empty($array['faults']) || !is_array($array['faults'])) {
			Log::warning('LibreNMS request could not be parsed or has no faults from ' . $request->ip() . ': ' . json_last_error_msg());
			return response()->json(['message' => 'No faults to process'], 200);
		}

		foreach($array['faults'] as $fault)
		{
	                $event = new Event;
	                $event->src_ip = substr($request->ip(),0,249);
	                $event->device_name = substr($array['device_name'],0,249);
	                $event->type = "NETWORK";
	                if($array['alert_state'] == 1)
	                {
	                        $event->resolved = 0;
	                } else {
	                        $event->resolved = 1;
	                }
	                $event->entity_type = substr($fault['entity_type'],0,249);
	                $event->entity_name = substr($fault['entity_name'],0,249);
	                $event->entity_desc = substr($fault['entity_desc'],0,9999);
	                $event->save();
					if(isset($event->id))
					{
						Log::info("Created event ID ". $event->id);
					}
		}
		return response()->json($event);
	}

	public function Scom(Request $request)
	{
		$message = "Received new request from " . $request->ip() . ":\n";
		$message .= $request;
		Log::info($message);
		$event = new Event;
		$event->src_ip = substr($request->ip(),0,249);
		$event->device_name = substr($request['DEVICE_HOSTNAME'],0,249);
		if($request['TYPE'])
		{
			$event->type = substr($request['TYPE'],0,249);
		} else {
			$event->type = "SERVER";
		}
		if($request['ALERT_STATE'] == "0")
		{
				$event->resolved = 0;
		} else{
				$event->resolved = 1;
		}
		$event->entity_type = substr($request['ENTITY_TYPE'],0,249);
		$event->entity_name = substr($request['ENTITY_NAME'],0,249);
		$event->entity_desc = substr($request['ENTITY_DESCRIPTION'],0,9999);
		$event->save();
		return $event;
	}

	public function Azure(Request $request)
	{
		$message = "Received new request from " . $request->ip() . ":\n";
		$message .= $request;
		Log::info($message);
		$event = new Event;
		$event->src_ip = substr($request->ip(),0,249);
		$event->type = strtoupper(substr($request['TYPE'],0,249));
		$event->device_name = substr($request['DEVICE'],0,249);
		$event->resolved = $request['RESOLVED'];
		$event->entity_type = substr($request['ENTITY_TYPE'],0,249);
		$event->entity_name = substr($request['ENTITY_NAME'],0,249);
		$event->entity_desc = substr(json_encode($request['ENTITY_DESC'],JSON_PRETTY_PRINT),0,9999);
		$event->save();
		return $event;
	}

	public function Report(Request $request)
	{
		$message = "Received new request from " . $request->ip() . ":\n";
		$message .= $request;
		Log::info($message);
		$event = new Event;
		$event->src_ip = substr($request->ip(),0,249);
		$event->type = "REPORT";
		$event->device_name = substr($request['DEVICE_NAME'],0,249);
		$event->resolved = $request['RESOLVED'];
		$event->entity_type = substr($request['ENTITY_TYPE'],0,249);
		$event->entity_name = substr($request['ENTITY_NAME'],0,249);
		$event->entity_desc = substr($request['ENTITY_DESCRIPTION'],0,9999);
		$event->save();
		return $event;
	}

}
