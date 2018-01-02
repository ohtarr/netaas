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
					if($ustates->count() > 1 || $state->resolved == 0)
					{
						print "Multiple devices from site or state is not resolved\n";
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

}
