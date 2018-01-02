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

	public function process()
	{
		$events = Event::where("processed",0)->get();
		
		foreach($events as $event)
		{
			$state = $event->get_state();
			if($state)
			{
				$state->resolved = $event->resolved;
				$state->processed = 0;
				$state->save();
			} else {
				$state = $event->create_state();
			}
			if($state)
			{
				$event->processed = 1;
				$event->save();
			}
		}
	}
}
