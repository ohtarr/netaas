<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\State;
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
		$this->process();
    }

	public static function process()
	{
		print "*******************************************\n";
		print "***********Processing States***************\n";
		print "*******************************************\n";
		$states = State::whereNull("incident_id")->get();
		if($states->isNotEmpty())
		{
			foreach($states as $state)
			{
				print "Processing STATE " . $state->name . "\n";
				$state = $state->fresh();
				$incident = $state->find_incident();
				if($incident)
				{
					print "Found incident " . $incident->name . ".\n";
					//Assign incident_id
					$state->incident_id = $incident->id;
					$state->save();
				} else {
					print "No incident found.\n";
					$ustates = $state->getUnassignedUniqueDeviceSiteStates();
					//if($ustates->count() > 1 || $state->resolved == 0)
					if($state->resolved == 0)
					{
						//print "Multiple devices from site or state is not resolved\n";
						print "State is not resolved\n";
						if($state->updated_at < Carbon::now()->subMinutes(env('TIMER_STATE_SAMPLING_DELAY')))
						{
							print "Sample time expired, creating incident!\n";
							$state->create_new_incident();
						}
					} else {
						if($state->updated_at < Carbon::now()->subMinutes(env('TIMER_DELETE_STALE_STATES')))
						{
							print "State is resolved and is now STALE, Deleting.\n";
							$state->delete();
						}
					}
				}
			}
		}
	}


	public static function processExistingDevice()
	{
		$states = State::whereNull("incident_id")->get();
		if($states->isNotEmpty())
		{
			foreach($states as $state)
			{
				print "Processing STATE " . $state->name . "\n";
				$state = $state->fresh();
				$deviceincident = Incident::where('name',$state->name)->first();
				if($deviceincident->isNotEmpty())
				{
					$state->incident_id = $deviceincident->id;
					$state->save();
				}
			}
		}	
	}

	public static function processExistingSite()
	{
		$states = State::whereNull("incident_id")->get();
		if($states->isNotEmpty())
		{
			foreach($states as $state)
			{
				print "Processing STATE " . $state->name . "\n";
				$state = $state->fresh();
				$siteincident = Incident::where('type', "site")->where('name', $state->get_sitecode())->first()

				if($siteincident->isNotEmpty())
				{
					$state->incident_id = $siteincident->id;
					$state->save();
				}
			}
		}	
	}

	public static function processExistingCompany()
	{
		$states = State::whereNull("incident_id")->get();
		if($states->isNotEmpty())
		{
			foreach($states as $state)
			{
				print "Processing STATE " . $state->name . "\n";
				$state = $state->fresh();
				$companyincident = Incident::where('type','company')->first();
				if($companyincident->isNotEmpty())
				{
					$state->incident_id = $companyincident->id;
					$state->save();
				}
			}
		}	
	}

	public static function processNewCompany()
	{
		$sites = State::getAllUnassignedSites();
		if(count($sites) > env("COMPANY_OUTAGE_COUNT"))
		{
			$newinc = Incident::create([
				'name'		=>	"company",
				'type'		=>	"company",
			]);
			$allUnassignedStates = State::whereNull("incident_id")->get();
			foreach($allUnassignedStates as $state)
			{
				$state->incident_id = $newinc->id;
				$state->save();
			}
		}
	}

	public static function processNewSite()
	{
		$allUnassignedStates = State::whereNull("incident_id")->get();
		foreach($allUnassignedStates as $state)
		{
			$siteStates = $state->getUnassignedUniqueDeviceSiteStates();
			if($siteStates->count() > 1) {
				$newinc = Incident::create([
					'name'		=>	$state->get_sitecode(),
					'type'		=>	"site",
				]);
				foreach($siteStates as $sitestate)
				{
					$sitestate->incident_id = $newinc->id;
					$sitestate->save();
				}
			}
		}	
	}

	public static function processNewNetworkDevice()
	{
		$allUnassignedStates = State::whereNull("incident_id")->get();
		foreach($allUnassignedStates as $state)
		{
			$siteStates = $state->getUnassignedUniqueDeviceSiteStates();
			if($siteStates->count() == 0) {
				$newinc = Incident::create([
					'name'		=>	$state->name,
					'type'		=>	"network_device",
				]);
				$state->incident_id = $newinc->id;
				$state->save();
			}
		}	
	}

	public static function processNewServerMultiple()
	{
		$allUnassignedStates = State::whereNull("incident_id")->get();
		foreach($allUnassignedStates as $state)
		{
			$siteStates = $state->getUnassignedUniqueDeviceSiteStates();
			if($siteStates->count() > 1) {
				$newinc = Incident::create([
					'name'		=>	$state->get_sitecode(),
					'type'		=>	"site",
				]);
				foreach($siteStates as $sitestate)
				{
					$sitestate->incident_id = $newinc->id;
					$sitestate->save();
				}
			}
		}	
	}

	public static function processNewServerSingle()
	{

	}

}
