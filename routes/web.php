<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
use App\Event;
use App\State;
use App\Incident;
use App\Ticket;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/netmon/', 'EventsController@Netmon');

Route::get('events', function() {
	return view('events',['events' => Event::all()]);
});

Route::get('states', function() {
	return view('states',['states' => State::all()]);
});

Route::get('incidents', function() {
	return view('incidents',['incidents' => Incident::all()]);
});

Route::get('stats', function() {
	return view('stats',[
		'events'	=>	Event::withTrashed()->get(),
		'states'	=>	State::withTrashed()->get(),
		'incidents' =>	Incident::withTrashed()->get(),
		'unassignedstates'	=>	State::withTrashed()->whereNull('incident_id')->get(),
		'siteincidents'	=>	Incident::withTrashed()->where("type","site")->get(),
		'deviceincidents'	=>	Incident::withTrashed()->where("type","device")->get(),
	]);
});