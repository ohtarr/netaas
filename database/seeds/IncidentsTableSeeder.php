<?php

use Illuminate\Database\Seeder;

class IncidentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
	$count = 1;
	$devices = [
			"khonelabrwa01",
			"khonelabswd01",
			"kostxingrwa01",
			"kostxingswd01",
			"noccaantrwa01",
			"noccaantswd01",
			"noccafairwa01",
			"noccafaiswd01",
			"tuscoengrwa01",
			"tuscoengswd01",
			"tuscoengswa01",
		];
	$sites = [
		"khonelab",
		"khonekbc",
	];

		foreach($devices as $device)
		{
			DB::table('incidents')->insert([
				'type'				=> "device",
				'name'				=> $device,
				'recover'			=> 0,
				'incident_number'	=> rand(1,10),
				'incident_status'	=> 1,
			]);		
		}
		foreach($sites as $site)
		{
			DB::table('incidents')->insert([
				'type'				=> "site",
				'name'				=> $site,
				'recover'			=> 0,
				'incident_number'	=> rand(1,10),
				'incident_status'	=> 1,
			]);		
		}
    }
}
