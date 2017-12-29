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

	public function process()
	{
		//$states = State::whereNull("incident_id")->where("type","device")->get();
		$states = State::whereNull("incident_id")->get();
		if($states->isNotEmpty())
		{
			foreach($states as $state)
			{
				$state = $state->fresh();
				$incident = $state->find_incident();
				if($incident)
				{
					//Assign incident_id
					$state->incident_id = $incident->id;
					$state->save();
				} else {
					//Create Incident
					if($state->updated_at < Carbon::now()->subMinutes(env('TIMER_STATE_SAMPLING_DELAY')))
					{
						$state->create_new_incident();
					} elseif ($state->updated_at < Carbon::now()->subMinutes(env('TIMER_DELETE_STALE_STATES')))
					{
						$state->delete();
					}
				}
			}
		}
	}

}
