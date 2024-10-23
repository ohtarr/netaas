<?php

use Illuminate\Support\Facades\Route;
use App\Models\Event;
use App\Models\State;
use App\Models\Incident;
use App\Models\Ticket;

Route::get('/', function () {
    return view('welcome');
});

Route::get('events', function() {
        return view('events',['events' => Event::getEventsLastDays(60)]);
});

Route::get('/events/lastdays/{days}', function($days){
        return view('events',['events' => Event::getEventsLastDays($days)]);
});

Route::get('/events/between/{date1}/{date2}', function($start, $end){
        return view('events',['events' => Event::getEventsBetween($start, $end)]);
});

Route::get('states', function() {
        return view('states',['states' => State::all()]);
});

Route::get('incidents', function() {
        return view('incidents',['incidents' => Incident::all()]);
});

Route::get('stats', function() {
        return view('stats',[
                'events_count'  =>      Event::withTrashed()->count(),
                'events_alert'  =>      Event::withTrashed()->where("resolved",0)->count(),
                'events_resolved'       =>      Event::withTrashed()->where("resolved",1)->count(),
                'states_count'  =>      State::withTrashed()->count(),
                'incidents' =>  Incident::withTrashed()->count(),
                'unassignedstates'      =>      State::withTrashed()->whereNull('incident_id')->count(),
                'siteincidents' =>      Incident::withTrashed()->where("type_id",1)->orWhere('type_id',2)->count(),
                'deviceincidents'       =>      Incident::withTrashed()->where("type_id",3)->orWhere('type_id',4)->count(),
        ]);
});
