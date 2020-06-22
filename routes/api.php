<?php

use Illuminate\Http\Request;
use App\Http\Controllers\EventsController;
use Carbon\Carbon;
use App\Event;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/netmon/', 'EventsController@Netmon');
Route::post('/scom/', 'EventsController@Scom');
Route::post('/azure/', 'EventsController@Azure');
Route::post('/report/', 'EventsController@Report');

Route::get('/events/', 'EventsController@getEvents');

Route::get('/events/between/{date1}/{date2}', function($start, $end){
	return Event::getEventsBetween($start, $end);
});

Route::get('/events/lastdays/{days}', function($days){
	return Event::getEventsLastDays($days);
});

Route::get('/events/yesterday/', 'EventsController@getYesterday');


