<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EventsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
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
			"easnjwclrwa01",
			"easnygoerwa01",
			"easnygoeswd01",
			"easnygoeswa01",
			"noccaantrwa02",
			"khonelabrwa02",
			"kpdkslenswd01",
			"kpdkslenrwa01",
			"tuscoengrwa01",
			"tuscoengswd01",
			"tuscoengswa01",
		];
		$createdat = Carbon::now()->subMinutes(30);
		foreach($devices as $device)
		{
			$createdat = $createdat->addMinute();
			DB::table('events')->insert([
				'created_at'		=> $createdat->toDateTimeString(),
				'src_ip'			=> "10.123.123.91",
				'src_type'			=> "netmon",
				'name'				=> $device,
				'type'				=> "device",
				'state'				=> 0,
			]);		
		}
		$devices = [
			"khonelabrwa01",
			"khonelabswd01",
			"kostxingrwa01",
			"kostxingswd01",
			"noccaantrwa01",
			"noccaantswd01",
		];
		$createdat = Carbon::now()->subMinutes(15);
		foreach($devices as $device)
		{
			$createdat = $createdat->addMinute();
			DB::table('events')->insert([
				'created_at'		=> $createdat->toDateTimeString(),
				'src_ip'			=> "10.123.123.91",
				'src_type'			=> "netmon",
				'name'				=> $device,
				'type'				=> "port",
				'state'				=> 0,
			]);		
		}

    }
}
