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
		$incidents = Incident::all();
		foreach($incidents as $incident)
		{
			if($incident->ticket)
			{
				if($ticket = Ticket::where('sysid',$incident->ticket)->first())
				{
					if($incident->resolved == 1 && $ticket->resolved == 0)
					{
						$this->resolve_ticket($incident);
					}
					if($incident->resolved == 0 && $ticket->resolved == 1)
					{
						$this->reopen_ticket($incident);
					}
				}			
			} else {
				$this->create_ticket($incident);
			}
			//$this->process_states($incident);
			$this->cleanup_states($incident);
		}
	}

	public function create_ticket($incident)
	{
			if($incident->type == "site")
			{
				$ticket = Ticket::create([
					'sysid'			=>	mt_rand(1000000000,9999999999),
					'type'			=>	"incident",
					'title'			=>	"MULTIPLE DEVICES DOWN AT SITE " . $incident->name ,
					'description'	=>	"There are multiple devices down at site " . $incident->name . ".  Please Fix!",
					'level'			=>	2,
				]);
			} else {
				$ticket = Ticket::create([
					'sysid'			=>	mt_rand(1000000000,9999999999),
					'type'			=>	"incident",
					'title'			=>	"DEVICE DOWN: " . $incident->name ,
					'description'	=>	"The following device is down: " . $incident->name . ".  Please Fix!",
					'level'			=>	3,
				]);
			}
			$incident->ticket = $ticket->sysid;
			$incident->save();
	}

	/*
	public function process_states($incident)
	{
		$states = State::where('incident_id',$incident->id)->get();
		foreach($states as $state)
		{
			if($state->processed == 0)
			{
				if($state->resolved == 0)
				{
					$status = "DOWN";
				} elseif ($state->resolved == 1) {
					$status = "UP";
				}
				$comment = "Device " . $state->name . " status changed to " . $status . ".";
				$this->update_ticket_comment($incident, $comment);
				$state->processed = 1;
				$state->save();
			}
		}
	}
/**/
	public function get_ticket($incident)
	{
	
	}
	
	public function update_ticket_comment($incident, $comment)
	{
		$states = State::where('processed', 0)->where('incident_id', '<>', null)->get();
		foreach($states as $state)
		{
			$incident = Incident::find($state->incident_id);
			if ($incident->ticket && $incident->active = 1)
			{
				$ticket = json_decode($incident->ticket);
				print_r($ticket);
				print $comment;
			}
		}
	}
	
	public function update_ticket_status($incident, $status)
	{
		
	}
	
	public function resolve_ticket($incident)
	{
		$ticket = Ticket::where('sysid',$incident->ticket)->first();
		if($ticket->resolved == 0)
		{
			$ticket->resolved = 1;
			$ticket->save();
		}
	}

	public function reopen_ticket($incident)
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
	
	
}
