<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/abcd', function () {
    return view('welcome');
});

Route::any('staging-server/insert', 'StagingServerController@insertProperty');
Route::get('staging-server/sync-crm','StagingServerController@syncCRM');
Route::get('/postPropertyFilterData','StagingServerController@postPropertyFilterData');
Route::get('/location-list-by-city/{city_id}','StagingServerController@getAreaList');
Route::get('/', function () {
    return view('welcome');
});

Route::any('staging-server/statistics/{count}', 'StagingServerController@getStatistics');
Route::any('staging-server/report/{id}', 'StagingServerController@getReport');
