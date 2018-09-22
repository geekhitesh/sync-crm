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
Route::any('staging-server/report-by-count/{count}', 'StagingServerController@getReportByCount');

Route::any('staging-server/report-by-filter/{filter_key}/{filter_value}', 'StagingServerController@getReportByFilter');

//File Handling


Route::get('/file/list','ReferenceDataController@printFileList');
Route::get('/file/view/{file_name}','ReferenceDataController@viewFile');
Route::get('/file/edit/{file_name}/{row_id}','ReferenceDataController@editFile');
Route::post('/file/save/','ReferenceDataController@saveFile');