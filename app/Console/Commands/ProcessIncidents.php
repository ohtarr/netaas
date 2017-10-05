<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\State;
use App\Incident;
use App\Ticket;

class ProcessIncidents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerter:process-incidents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process incidents and manage opening/updating tickets';

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
		$this->process_incidents();
    }

/*
process_incidents()
	create_tickets()
		create_ticket($incident)
	update_tickets()

		update_ticket_comment($incident,$comment)
			get_ticket($incident)
		update_ticket_status($incident)
			get_ticket($incident)

	cleanup_states()	
/**/

	public function process_incidents()
	{

	}
	
	public function cleanup_states($incident)
	{
		if($incident->trashed())
		{
			$states = State::where('incident_id',$incident->id)-get();
			foreach($states as $state)
			{
				$state->delete();
			}
		}
	}
	
	public function process_ticket($incident)
	{
		if($ticket = Ticket::where('sysid',$incident->ticket)->withTrashed()->first())
		{
			if($ticket->trashed())
			{
				foreach(State::where('incident_id',$incident->id)->get() as $state)
				{
					$state->delete();
				}
				$incident->delete();
			} elseif ($ticket->resolved == 1 && $incident->resolved == 0){
				foreach(State::where('incident_id',$incident->id)->get() as $state)
				{
					$state->delete();
				}
				$incident->resolved = 1;
				$incident->save();
			}
		}
	}
}
