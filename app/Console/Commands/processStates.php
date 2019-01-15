<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\State;
use App\Incident;
use App\IncidentType;
use Carbon\Carbon;

class processStates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netaas:processStates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all unassigned states.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
		$this->processExistingDevice();
		$this->processExistingSite();
		$this->processExistingCompany();
		$this->processNewCompany();
		$this->processNewSite();
		$this->processNewDeviceNetwork();
		$this->processNewDeviceServer();
		$this->processStale();
	}

	public static function processExistingDevice()
	{
		$inctype = IncidentType::where("name","DEVICE_NETWORK_LOW")->first();
		if(!$inctype)
		{
			return;
		}
		$states = State::whereNull("incident_id")->get();
		foreach($states as $state)
		{
			$state->refresh();
			$deviceincident = Incident::where("type_id",$inctype->id)->where('name',$state->device_name)->first();
			if($deviceincident)
			{
				$devicestates = State::whereNull("incident_id")->where('device_name',$state->device_name)->get();
				foreach($devicestates as $devicestate)
				{
					$devicestate->incident_id = $deviceincident->id;
					$devicestate->save();
				}
				return $deviceincident;
			}
		}
	}

	public static function processExistingSite()
	{
		$inctypes = IncidentType::where("name","SITE_HIGH")->orWhere("name","SITE_MEDIUM")->get();
		if($inctypes->isEmpty())
		{
			return;
		}
		$states = State::whereNull("incident_id")->get();
		foreach($inctypes as $inctype)
		{
			foreach($states as $state)
			{
				$state->refresh();
				$siteincident = Incident::where('type_id',$inctype->id)->where('name', $state->get_sitecode())->first();

				if($siteincident)
				{
					$sitestates = $state->getUnassignedUniqueDeviceSiteStates();
					foreach($sitestates as $sitestate)
					{
						foreach($sitestate as $sitedevicestate)
						{
							$sitedevicestate->incident_id = $siteincident->id;
							$sitedevicestate->save();
						}
					}
					return $siteincident;
				}
			}
		}
	}

	public static function processExistingCompany()
	{
		$inctype = IncidentType::where("name","COMPANY_CRITICAL")->first();
		if(!$inctype)
		{
			return;
		}
		$companyincident = Incident::where('type_id',$inctype->id)->first();
		if($companyincident)
		{
			$states = State::whereNull("incident_id")->get();
			foreach($states as $state)
			{
				print "Adding state " . $state->device_name . " to company outage!!\n";
				$state->incident_id = $companyincident->id;
				$state->save();
			}
			return $companyincident;
		}
	}

	public static function processNewCompany()
	{
		$inctype = IncidentType::where("name","COMPANY_CRITICAL")->first();
		$states = State::whereNull("incident_id")->where('updated_at',"<",Carbon::now()->subMinutes(env('TIMER_STATE_SAMPLING_DELAY')))->get();
		if($states->isEmpty())
		{
			return;
		}
		$states = State::whereNull("incident_id")->get();
		$sites = [];
		$devices = $states->groupBy('device_name');
		foreach($devices as $device)
		{
			foreach($device as $entity)
			{
				$sites[] = substr($entity->device_name,0,8);
			}
		}
		$sites = array_unique($sites);
		if(count($sites) < env("COMPANY_OUTAGE_COUNT"))
		{
			return;
		}
		$newinc = Incident::create([
			'name'		=>	"company",
			'type_id'	=>	$inctype->id,
		]);
		foreach($states as $state)
		{
			$state->incident_id = $newinc->id;
			$state->processed = 1;
			$state->save();
		}
		return $newinc;
	}

	public static function processNewSite()
	{
		$states = State::whereNull("incident_id")->where('updated_at',"<",Carbon::now()->subMinutes(env('TIMER_STATE_SAMPLING_DELAY')))->get();
		//print_r($states);
		foreach($states as $state)
		{
			$state->refresh();
			$location = $state->get_location();
			print "State " . $state->device_name . "\n";
			if($location)
			{
				if($location->u_priority == 2)
				{
					$inctype = IncidentType::where("name","SITE_HIGH")->first();
				}
			} else {
				$inctype = IncidentType::where("name","SITE_MEDIUM")->first();
			}
			if(!$inctype)
			{
				return;
			}
			$siteStates = $state->getUnassignedUniqueDeviceSiteStates();

			if($siteStates->count() > 1)
			{
				$newinc = Incident::create([
					'name'		=>	$state->get_sitecode(),
					'type_id'	=>	$inctype->id,
				]);
				foreach($siteStates as $sitestate)
				{
					foreach($sitestate as $sitedevicestate)
					{
						$sitedevicestate->incident_id = $newinc->id;
						$sitedevicestate->processed = 1;
						$sitedevicestate->save();
					}
				}
				return $newinc;
			}
		}
	}

	public static function processNewDeviceNetwork()
	{
		$states = State::whereNull("incident_id")->where('updated_at',"<",Carbon::now()->subMinutes(env('TIMER_STATE_SAMPLING_DELAY')))->where("type","NETWORK")->get();
		foreach($states as $state)
		{
			$state->refresh();
			$inctype = IncidentType::where("name","DEVICE_NETWORK_LOW")->first();
			if(!$inctype)
			{
				return;
			}
			$siteStates = $state->getUnassignedUniqueDeviceSiteStates();
			if($siteStates->count() == 1 && $siteStates->first()->first()->resolved == 0)
			{
				$newinc = Incident::create([
					'name'		=>	$state->device_name,
					'type_id'	=>	$inctype->id,
				]);
				$devicestates = State::whereNull("incident_id")->where('device_name',$state->device_name)->get();
				foreach($devicestates as $devicestate)
				{
					$devicestate->incident_id = $newinc->id;
					$devicestate->processed = 1;
					$devicestate->save();
				}
				return $newinc;
			}
		}
	}

	public static function processNewDeviceServer()
	{
		$states = State::whereNull("incident_id")->where('updated_at',"<",Carbon::now()->subMinutes(env('TIMER_STATE_SAMPLING_DELAY')))->where("type","SERVER")->get();
		foreach($states as $state)
		{
			$state->refresh();
			$inctype = IncidentType::where("name","DEVICE_SERVER_HIGH")->first();
			if(!$inctype)
			{
				return;
			}
			$siteStates = $state->getUnassignedUniqueDeviceSiteStates();
			if($siteStates->count() <= 1)
			{
				$newinc = Incident::create([
					'name'		=>	$state->device_name,
					'type_id'	=>	$inctype->id,
				]);
				foreach($siteStates as $sitestate)
				{
					foreach($sitestate as $sitedevicestate)
					{
						$sitedevicestate->incident_id = $newinc->id;
						$sitedevicestate->processed = 1;
						$sitedevicestate->save();
					}
				}
				return $newinc;
			}
		}
	}
	public static function processStale()
	{
		$states = State::whereNull("incident_id")->where('resolved',1)->where('updated_at',"<",Carbon::now()->subMinutes(env('TIMER_DELETE_STALE_STATES')))->get();
		foreach($states as $state)
		{
			$state->refresh();
			$state->delete();
		}
	}

}
