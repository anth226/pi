<?php

use Illuminate\Http\Request;

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

//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});


//Route::middleware('auth:api')->group( function () {
	Route::post('stripe', 'API\StripeController@stripe');
//});


//Route::middleware('auth:api')->group(function(){
    Route::namespace('API')->group(function(){
        Route::prefix('customers')->group(function(){
            Route::get('/', 'CustomerController@index');
            Route::post('/', 'CustomerController@store');
            Route::get('/{customer}', 'CustomerController@detail');
            Route::put('/{customer}', 'CustomerController@postUpdate');
        });
    });
//});
