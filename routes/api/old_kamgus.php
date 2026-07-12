<?php

use App\Http\Controllers\Dashboard_V1\ServiceController;
use App\Http\Controllers\Dashboard_V1\TransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v2')->group(function () {
    Route::get("dashboard_v1/service/getopcionesNV3/{id}", [ServiceController::class, 'show2']);   
    Route::group([
        'middleware' => 'auth:api',
        'prefix' => "dashboard_v1"
    ], function() {
        
        Route::get("vehicle", [InvitedsServiceController::class, 'store']);
        Route::put("service/{id}", [ServiceController::class, 'update']);
        Route::post("service/{id}", [ServiceController::class, 'store2']);   
        Route::put("transaction/{id}", [TransactionController::class, 'update']);
        Route::get("transaction/balance/{id}", [TransactionController::class, 'calculateBalance']);
    });
}); 
