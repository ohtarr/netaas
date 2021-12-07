<?php

namespace App;

use ohtarr\ServiceNowModel;

class ServiceNowIncident extends ServiceNowModel
{
	protected $guarded = [];

	public $table = "incident";
	
	public function close($msg)
	{
		$message = "ServiceNowIncident ID " . $this->sys_id . " Close";
		print $message . "\n";
		Log::info($message);
		$this->u_cause_code = "Environment";
		$this->close_notes = $msg;
		$this->state = 6;
		$this->save();
	}

	public function open()
	{
		$message = "ServiceNowIncident ID " . $this->sys_id . " Open";
		print $message . "\n";
		Log::info($message);
		$this->state = 2;
		$this->save();
	}

	public function add_comment($comment)
	{
		$message = "ServiceNowIncident ID " . $this->sys_id . " add_comment";
		print $message . "\n";
		Log::info($message);
		$this->comments = $comment;
		$this->save();
	}
	
	public function get_incident()
	{
		return Incident::where('ticket', $this->sys_id)->first();
	}
	
	public function isOpen()
	{
		if ($this->state == 4 || $this->state == 6 || $this->state == 7)
		{
			return false;
		} else {
			return true;
		}
	}
	
	public function getPriorityString()
	{
		$string = null;
		if($this->priority == 1)
		{
			$string =  "critical";
		}
		if($this->priority == 2)
		{
			$string = "high";
		}
		if($this->priority == 3)
		{
			$string = "medium";
		}
		if($this->priority == 4)
		{
			$string = "low";
		}
		return $string;
	}

	public function cancel_unused_tickets()
	{
		$tickets = $this->all_mine();
		foreach($tickets as $ticket)
		{
			print $ticket->number . "\n";
			$incident = Incident::where("ticket",$ticket->sys_id)->first();

			if($incident)
			{
				print $incident->name . "\n";
				$ticket->caller_id = '45895b236f7d07845d6dcd364b3ee438';
				$ticket->save();
			} else {
				$ticket->add_comment('This ticket is orphaned from the Netaas system.  Closing.');
				$ticket->state = 4;
				$ticket->caller_id = '5c004d166fe5110034cb07321c3ee442';
				$ticket->save();
			}
		}
	}
	
	public function cancel_all_my_incidents()
	{
		$tickets = $this->all_mine();
		foreach($tickets as $ticket)
		{
			if($ticket->active == 1)
			{
				print $ticket->number . "\n";
				$ticket->state = 4;
				if($ticket->isDirty())
				{
					$ticket->save();
				}
			}
		}
	}

}