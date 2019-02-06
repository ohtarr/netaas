<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Incident;
use Carbon\Carbon;

class processIncidents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netaas:processIncidents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all Incidents';

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
		$this->processIncidents();
    }
       
    public function processNew($incident)
    {
        if($incident->ticket_id)
        {
            return null;
        }
        $incident->createTicket();
    }

    public function processClosed($incident)
    {
        $ticket = $incident->get_ticket();
        if($ticket->state == 7 || $ticket->state == 4)
        {
            //Purge this incident and all related states.
            $incident->purgeStates();
            $incident->delete();
            return $ticket;
        }
        return null;
    }

    public function processResolved($incident)
    {
        $ticket = $incident->get_ticket();
        if ($ticket->state == 6)
        {
            if($incident->isOpen())
            {
                $msg = "Manual ticket closure was detected.  Clearing " . $incident->name . " from Netaas system.\n";
                $msg .= "Current status of States: \n";
                $msg .= $incident->getStateStatus();
                //ADD COMMENT TO TICKET
                $ticket->add_comment($msg);
                //Set incident to RESOLVED
                if($incident->incidentType->name == "COMPANY_CRITICAL")
                {
                    //Purge all states attached to this incident and the incident.
                    $incident->purgeStates();
                    $incident->delete();
                } else {
                    //Purge all states attached to this incident.
                    $incident->purgeStates();
                    $incident->close();
                }
            } else {
                //If there are unresolved states, reopen ticket
                $unstates = $incident->get_unresolved_states();
                if($unstates->isNotEmpty())
                {
                    $incident->reopenTicket();
                } elseif($incident->updated_at->lt(Carbon::now()->subHours(env('TIMER_AUTO_RELEASE_TICKET')))) {
                    $ticket->add_comment("This ticket has been in a resolved state for over " . env('TIMER_AUTO_RELEASE_TICKET') . " hours. This ticket is no longer tracked by the Netaas system.");
                    $incident->purgeStates();
                    $incident->delete();
                }
            }
        }
        return null;
    }

    public function processOpen($incident)
    {
        $ticket = $incident->get_ticket();
        if(!$ticket)
        {
            return null;
        }
        if ($ticket->state == 6 && $ticket->state == 7 && $ticket->state == 4)
        {
            return null;
        }
        if($incident->isOpen())
        {
            $unpstates = $incident->get_unprocessed_states();
            if($unpstates->isNotEmpty())
            {
                $incident->updateTicket();
            }
            if($unstates->isEmpty())
            {
                if($incident->get_latest_state()->updated_at->lt(Carbon::now()->subMinutes(env('TIMER_AUTO_RESOLVE_TICKET'))))
                {
                    $incident->autoCloseTicket();
                }
            }
        //IF INCIDENT IS CLOSED
        } else {
            if($unstates->isEmpty())
            {
                $msg = "Ticket was manually re-opened.  Currently there are NO devices in an ALERT state.";
            } else {
                $msg = "Ticket was manually re-opened.  The following are currently in an ALERT state: \n";
                $msg .= $incident->getStateStatus();
            }
            $ticket->add_comment($msg);
            $incident->resolved = 0;
            $incident->save();
        }
    }

    public function processIncidents()
	{
		print "*******************************************\n";
		print "***********Processing Incidents************\n";
		print "*******************************************\n";
        $incidents = Incident::all();
        foreach($incidents as $incident)
        {
            print "Processing INCIDENT " . $incident->name . "\n";
            //Fetch me our ticket
            $ticket = $incident->get_ticket();
            $unstates = $incident->get_unresolved_states();
            $unpstates = $incident->get_unprocessed_states();
            
            if($ticket)
            {
                //if the service now ticket is CLOSED (not resolved, but completely closed or cancelled)
                if($ticket->state == 7 || $ticket->state == 4)
                {
                    //Purge this incident and all related states.
                    $incident->purge();
                //If the SNOW ticket is in RESOLVED state
                } elseif ($ticket->state == 6) {
                    //IF INCIDENT IS NOT RESOLVED
                    if($incident->isOpen())
                    {
                        $msg = "Manual ticket closure was detected.  Clearing " . $incident->name . " from Netaas system.\n";
                        $msg .= "Current status of States: \n";
                        $msg .= $incident->compileStateSummary();
                        //ADD COMMENT TO TICKET
                        $ticket->add_comment($msg);
                        //Set incident to RESOLVED
                        if($incident->type == "company")
                        {
                            //Purge all states attached to this incident and the incident.
                            $incident->purge();
                        } else {
                            //Purge all states attached to this incident.
                            $incident->purgeStates();
                            $incident->close();
                        }
                    //IF INCIDENT IS RESOLVED
                    } else {
                        //If there are unresolved states, reopen ticket
                        if($unstates->isNotEmpty())
                        {
                            $incident->reopenTicket();
                        } elseif($incident->updated_at->lt(Carbon::now()->subHours($incident->incidentType->ticketrelease))) {
                            $ticket->add_comment("This ticket has been in a resolved state for over " . $incident->incidentType->ticketrelease . " hours. This ticket is no longer tracked by the Netaas system.");
                            $incident->purge();
                        }
                    }
                //If the SNOW ticket is OPEN
                } else {
                    //IF INCIDENT IS OPEN
                    if($incident->isOpen())
                    {
                        if($unpstates->isNotEmpty())
                        {
                            $incident->updateTicket();
                        }
                        if($unstates->isEmpty())
                        {
                            if($incident->get_latest_state())
                            {
                                if($incident->get_latest_state()->updated_at->lt(Carbon::now()->subMinutes($incident->incidentType->autoresolve)))
                                {
                                    $incident->autoCloseTicket();
                                }
                            }
                        }
                        foreach($unpstates as $state)
                        {
                            $state->processed = 1;
                            $state->save();
                        }
                    //IF INCIDENT IS CLOSED
                    } else {
                        if($unstates->isEmpty())
                        {
                            $msg = "Ticket was manually re-opened.  Currently there are NO devices in an ALERT state.";
                        } else {
                            $msg = "Ticket was manually re-opened.  The following are currently in an ALERT state: \n";
                            $msg .= $incident->getStateStatus();
                        }
                        $ticket->add_comment($msg);
                        $incident->resolved = 0;
                        $incident->save();
                    }
                }
            //IF THERE IS NO SNOW TICKET
            } else {
                //IF TYPE IS SITE OR COMPANY OR THERE ARE UNRESOLVED STATES
                //if($incident->incidentType->name == "SITE_HIGH" || $incident->incidentType->name == "COMPANY_CRITICAL" || $unstates->isNotEmpty())
                //{
                    //Create a new snow ticket
                    print $incident->name . "Creating a SNOW ticket!\n";
                    $incident->createTicket();
                //}
            }
        }
    }

}
