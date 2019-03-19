<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedStates();
    }

    public function seedStates()
    {
		$devices = [
            [
                'device_name'   =>  "aaabbcccrwa01",
                'type'          =>  "NETWORK",
                'entity_type'   =>  "DEVICE_STATUS",
                'entity_name'   =>  "",
                'entity_desc'   =>  "",
                'resolved'      =>  0,
            ],
			[
                'device_name'   =>  "aaabbcccrwa02",
                'type'          =>  "NETWORK",
                'entity_type'   =>  "DEVICE_STATUS",
                'entity_name'   =>  "",
                'entity_desc'   =>  "",
                'resolved'      =>  0,
            ],
			[
                'device_name'   =>  "aaabbcccrwa03",
                'type'          =>  "NETWORK",
                'entity_type'   =>  "DEVICE_STATUS",
                'entity_name'   =>  "",
                'entity_desc'   =>  "",
                'resolved'      =>  0,
            ],
			[
                'device_name'   =>  "aaabbdddrwa01",
                'type'          =>  "NETWORK",
                'entity_type'   =>  "DEVICE_STATUS",
                'entity_name'   =>  "",
                'entity_desc'   =>  "",
                'resolved'      =>  0,
            ],
            [
                'device_name'   =>  "aaabbeeerwa01",
                'type'          =>  "NETWORK",
                'entity_type'   =>  "PORT_STATUS",
                'entity_name'   =>  "GigabitEthernet0/0",
                'entity_desc'   =>  "uplink",
                'resolved'      =>  0,
            ],
            [
                'device_name'   =>  "aaabbeeerwa01",
                'type'          =>  "NETWORK",
                'entity_type'   =>  "PORT_STATUS",
                'entity_name'   =>  "GigabitEthernet0/1",
                'entity_desc'   =>  "uplink",
                'resolved'      =>  0,
            ],
            [
                'device_name'   =>  "aaabbeeerwa01",
                'type'          =>  "NETWORK",
                'entity_type'   =>  "PORT_STATUS",
                'entity_name'   =>  "GigabitEthernet0/2",
                'entity_desc'   =>  "uplink",
                'resolved'      =>  0,
            ],
            [
                'device_name'   =>  "aaabbfffrwa01",
                'type'          =>  "NETWORK",
                'entity_type'   =>  "DEVICE_STATUS",
                'entity_name'   =>  "",
                'entity_desc'   =>  "",
                'resolved'      =>  0,
            ],
            [
                'device_name'   =>  "aaabbfffvsh01",
                'type'          =>  "SERVER_WINDOWS",
                'entity_type'   =>  "DEVICE_STATUS",
                'entity_name'   =>  "",
                'entity_desc'   =>  "",
                'resolved'      =>  0,
            ],
            [
                'device_name'   =>  "aaabbgggrwa01",
                'type'          =>  "NETWORK",
                'entity_type'   =>  "DEVICE_STATUS",
                'entity_name'   =>  "",
                'entity_desc'   =>  "",
                'resolved'      =>  0,
            ],
            [
                'device_name'   =>  "aaabbgggrwa02",
                'type'          =>  "NETWORK",
                'entity_type'   =>  "DEVICE_STATUS",
                'entity_name'   =>  "",
                'entity_desc'   =>  "",
                'resolved'      =>  0,
            ],
            [
                'device_name'   =>  "aaabbgggvsh01",
                'type'          =>  "SERVER_WINDOWS",
                'entity_type'   =>  "DEVICE_STATUS",
                'entity_name'   =>  "",
                'entity_desc'   =>  "",
                'resolved'      =>  0,
            ],
            [
                'device_name'   =>  "aaabbhhhvsh01",
                'type'          =>  "SERVER_WINDOWS",
                'entity_type'   =>  "DEVICE_STATUS",
                'entity_name'   =>  "",
                'entity_desc'   =>  "",
                'resolved'      =>  0,
            ],
            [
                'device_name'   =>  "aaabbhhhvsh02",
                'type'          =>  "SERVER_WINDOWS",
                'entity_type'   =>  "DEVICE_STATUS",
                'entity_name'   =>  "",
                'entity_desc'   =>  "",
                'resolved'      =>  0,
            ],
            [
                'device_name'   =>  "aaabbiiivsh02",
                'type'          =>  "SERVER_WINDOWS",
                'entity_type'   =>  "DEVICE_STATUS",
                'entity_name'   =>  "",
                'entity_desc'   =>  "",
                'resolved'      =>  1,
            ],
            [
                'device_name'   =>  "aaabbjjjrwa02",
                'type'          =>  "NETWORK",
                'entity_type'   =>  "DEVICE_STATUS",
                'entity_name'   =>  "",
                'entity_desc'   =>  "",
                'resolved'      =>  1,
            ],
		];
		$createdat = Carbon::now()->subMinutes(20);
		foreach($devices as $device)
		{
            $createdat = $createdat->addMinute();
			DB::table('states')->insert([
                'created_at'		=> $createdat->toDateTimeString(),
                'updated_at'		=> $createdat->toDateTimeString(),
				'type'				=> $device["type"],
				'device_name'		=> $device['device_name'],
				'resolved'			=> $device['resolved'],
				'entity_type'		=> $device['entity_type'],
				'entity_name'		=> $device['entity_name'],
                'entity_desc'		=> $device['entity_desc'],
                'processed'         => 0,
                'incident_id'       => null,
			]);
		}
    }
}
