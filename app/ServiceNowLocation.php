<?php

namespace App;

use ohtarr\ServiceNowModel;
use GuzzleHttp\Client as GuzzleHttpClient;
use JJG\Ping as Ping;
use App\ServiceNowUser;

class ServiceNowLocation extends ServiceNowModel
{
	protected $guarded = [];

	public $table = "cmn_location";

	public function __construct(array $attributes = [])
	{
			$this->snowbaseurl = env('SNOW_API_URL');
			$this->snowusername = env("SNOW_USERNAME");
			$this->snowpassword = env("SNOW_PASSWORD");
			parent::__construct($attributes);
	}

	public function isActive()
	{
		if($this->u_network_mob_date && !$this->u_network_demob_date)
		{
			return true;
		}		
	}

	public function getContact()
	{
		if(isset($this->contact["value"]))
		{
			$user = $this->contact['value'];
			return ServiceNowUser::find($user);
		}
		return null;
	}

	public function getPriorityString()
	{
		if($this->u_priority == 0)
		{
			return "NO MONITORING";
		}
		if($this->u_priority == 1)
		{
			return "NEXT BUSINESS DAY";
		}
		if($this->u_priority == 2)
		{
			return "24/7";
		}
	}
	
	public function getWeather()
	{
		$client = new GuzzleHttpClient;
		$verb = 'GET';
		$url = env('WEATHER_API_BASE_URL') . "?lat=" . $this->latitude . "&lon=" . $this->longitude . "&appid=" . env('WEATHER_API_KEY') . "&units=imperial";
		//Perform the api call
		$response = $client->request($verb, $url);
		//get the body contents and decode json into an array.
		try
		{
			$array = json_decode($response->getBody()->getContents(), true);
		} catch(\Exception $e) {
		
		}
		if($array)
		{
			$weatherdesc = $array['weather'][0]['main'] . ", " . $array['weather'][0]['description'] . ", Temp: " . $array['main']['temp'] . "F , Windspeed: " . $array['wind']['speed'] . "MPH";
		}
		return $weatherdesc;
	}

	public function getOpengear()
	{
		$host = $this->name . "oob01." . env('FQDN');
		$ip = gethostbyname($host);
		if (filter_var($ip, FILTER_VALIDATE_IP)) {
			$ping = new Ping($host);
			$ping->setTimeout(5);		
			$status = $ping->ping();
			if($status)
			{
				return "UP!";
			} else {
				return "DOWN!";
			}
		} else {
			return null;
		}
	}

}
