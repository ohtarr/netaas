<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Event;

class processEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netaas:processEvents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all unprocessed Events';

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
		print "***********Processing Events***************\n";
		print "*******************************************\n";
		$events = Event::where("processed",0)->get();
		
		foreach($events as $event)
		{
			print "Processing EVENT " . $event->name . "\n";
			$state = $event->get_state();
			if($state)
			{
				print "State " . $state->name . " found, updating State.\n";
				$state->resolved = $event->resolved;
				$state->processed = 0;
				$state->save();
			} else {
				print "No State found.  Creating a new State.\n";
				$state = $event->create_state();
			}
			if($state)
			{
				print "Flagging Event processed.\n";
				$event->processed = 1;
				$event->save();
			}
		}
	}
}
