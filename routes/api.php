<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventsController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//Route::post('/netmon/', 'EventsController@Netmon');
Route::post('/netmon/', [EventsController::class,"Netmon"]);