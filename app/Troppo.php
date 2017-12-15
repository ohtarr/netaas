<?php

namespace App;

use GuzzleHttp\Client as GuzzleHttpClient;

class Troppo
{

	public static function callVoice($number, $msg)
	{
		$paramsarray = [
			"token"		=>	env("TROPO_TOKEN"),
			"from"		=>	env("TROPO_CALLERID"),
			"msg"		=>	$msg,
			"number"	=>	$number,
		];
		$params['body'] = json_encode($paramsarray);
		$params['headers'] = [
			'Content-Type'	=> 'application/json',
			'Accept'		=> 'application/json',
		];
		$client = new GuzzleHttpClient;
		try
		{
			$response = $client->request("POST", env("TROPO_BASE_URL"), $params);
		} catch(\Exception $e) {
		
		}
		//get the body contents and decode json into an array.
		$array = json_decode($response->getBody()->getContents(), true);
		return $array['success'];
	}
	
	public static function stringToVoice($name)
	{
		return implode(" ", str_split($name));	
	}

}
