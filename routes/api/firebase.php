<?php

use App\Http\Controllers\Dashboard\NotificationsController;
use Illuminate\Support\Facades\Route;  
use App\Http\Controllers\TokenFCM;

Route::group(['prefix' => 'v2', 'middleware' => 'auth:api'], function () {

    //guardar FCM Token del usuario para firebase
    Route::post('fcm/token', [TokenFCM::class, 'store']);

    //actualizar el fcm token
    Route::put('fcm/token', [TokenFCM::class, 'update']);
    Route::post('notification/{id}', [NotificationsController::class, 'store']);

});

