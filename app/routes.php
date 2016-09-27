<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::get('/', function () {
    return View::make('hello');
});

Route::group(['prefix' => 'paypal'], function () {
    Route::get('/crear-plan-regular', 'PaymentController@crearPlaRegular');
    Route::get('/crear-plan-trial', 'PaymentController@crearPlanTrial');
    Route::get('/payment-success', 'PaymentController@pagoSatifactorio');
    Route::get('/payment-cancel', 'PaymentController@pagoCancelado');
    Route::get('/subscription-cancel', 'PaymentController@cancelar');
    Route::post('/ipn/listener', 'PaypalHookController@ipn');
});
