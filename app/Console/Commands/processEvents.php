<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Event;
use Illuminate\Support\Facades\Log;

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
            $message = "processEvents Processing Event " . $event->id . " for device " . $event->device_name;
            print $message . "\n";
            Log::info($message);
			$state = $event->get_state();
			if($state)
			{
                $message = "processEvents State with ID " . $state->id . " found for event " . $event->device_name . " with ID " . $event->id . " .. updating State";
                print $message . "\n";
                Log::info($message);
                $state = $event->update_state();
			} else {
                $message = "processEvents State NOT FOUND for event " . $event->device_name . " with ID " . $event->id . " Creating new State.";
                print $message . "\n";
                Log::info($message);
                $state = $event->create_state();
			}
			if($state)
			{
                $message = "processEvents Flagging EVENT " . $event->device_name . " with ID " . $event->id . " processed.";
                print $message . "\n";
                Log::info($message);
                $event->mark_processed();
			}
		}
	}
}
