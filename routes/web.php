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
use App\Incident;

Route::get('/', function () {
    return view('welcome');
});

Route::get('foo', function () {
    return "Hello!";
});

Route::view('/welcome', 'welcome', ['name' => 'Taylor']);

Route::get('/test', 'Events@test');

Route::get('/add/{name}', 'EventsController@addDB');

Route::post('/netmon/', 'EventsController@Netmon');

Route::get('/recent', 'IncidentsController@process_events');

Route::get('/timetest/{time1}/{time2}', 'IncidentsController@get_event_window');

Route::get('incidents', function() {
	return view('incidents',['incidents' => Incident::all()]);
});