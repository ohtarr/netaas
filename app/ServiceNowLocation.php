<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\ServiceNowUser as ServiceNowUser;
use GuzzleHttp\Client as GuzzleHttpClient;
use JJG\Ping as Ping;

class ServiceNowLocation extends Model
{
	protected $guarded = [];

	public $table = "cmn_location";

	public function newQuery()
	{
		$builder = new ServiceNowQueryBuilder();
		$builder->setModel($this);
		return $builder;
	}

	//get all table records
    public static function all($columns = ['*'])
    {
		$instance = new static;
		return $instance->newQuery()
						->get();
    }

	//Find a snow ticket via sysid
	public static function find($sysid)
	{
		$instance = new static;
		return $instance->newQuery()
						->where('sys_id',"=", $sysid)
						->get()
						->first();
	}

	//Update a snow ticket
	public function save(array $options = [])
	{

	}

	//Create a new Snow Ticket
	public static function create($attribs = [])
	{
	
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
	
	public function getWeather()
	{
		$client = new GuzzleHttpClient;
		$verb = 'GET';
		$url = env('WEATHER_API_BASE_URL') . "?lat=" . $this->latitude . "&lon=" . $this->longitude . "&appid=" . env('WEATHER_API_KEY') . "&units=imperial";
		//Perform the api call
		$response = $client->request($verb, $url);
		//get the body contents and decode json into an array.
		$array = json_decode($response->getBody()->getContents(), true);
		//print_r($array);
		$weatherdesc = $array['weather'][0]['main'] . ", " . $array['weather'][0]['description'] . ", Temp: " . $array['main']['temp'] . "F , Windspeed: " . $array['wind']['speed'] . "MPH";
		return $weatherdesc;
	}

	public function getOpengear()
	{
		$host = $this->name . "oob01";
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