<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\V2\Drivers\UserController as DriversUserController;
use App\Http\Controllers\V2\Drivers\ServicesController as DriversServicesController;
use App\Http\Controllers\V2\Drivers\ServiceStatusController;
use App\Http\Controllers\V2\Drivers\UserBankAccount;
use App\Http\Controllers\V2\Drivers\UserPaymentController;
use App\Http\Controllers\V2\Drivers\UserPayoutController;
use App\Http\Controllers\V2\Drivers\UserVehicleController as DriverUserVehicleController;
use App\Http\Controllers\V2\Drivers\VehiclesController as DriverVehiclesController;
use App\Http\Controllers\V2\Drivers\WebhooksController;

Route::prefix('v2')->group(function () {
    Route::post('login/driver', [DriversUserController::class, 'store']);
    Route::post('driver/delete_account', [DriversUserController::class, 'deleteUser']);
    Route::get('forgot/driver/{id}', [DriversUserController::class, 'show']);
    Route::post('forgot', [DriversUserController::class, 'forgot2']);
    
    Route::get("driver/transaction_status/{id}", [UserPaymentController::class, 'show2']);
    Route::match(['get', 'post'],'driver/wh/yappy_status', [WebhooksController::class, 'yappyStatus']);
    Route::match(['get', 'post'],'driver/wh/stripe_status', [WebhooksController::class, 'stripeStatus']);
    Route::match(['get', 'post'],'driver/wh/pago_cash_status', [WebhooksController::class, 'pagoCashStatus']);

    
    Route::group([
        'middleware' => 'auth:api',
        'prefix' => "driver"
    ], function() {
        /**
         * Despliegue y actualizacion de datos de un conductor
         */
        Route::get('profile', [DriversUserController::class, 'index']);
        Route::put('profile/{id}', [DriversUserController::class, 'update']);
        Route::post('profile/update', [DriversUserController::class, 'storeProfile']);
        Route::delete('delete_account', [DriversUserController::class, 'deleteUserLoggued']);

        /**
         * Despliegue y gestion de servicios de un conductor
         */
        Route::get("services", [DriversServicesController::class, 'index'])->middleware("has_balance");
        Route::post("services/{id?}", [DriversServicesController::class, 'store'])->middleware(["has_balance", "status:Activo"]);
        Route::get("services/{id}", [DriversServicesController::class, 'show']);
        Route::put("services/{id}", [DriversServicesController::class, 'update']);

        /**
         * Despliegue y actualizacion de vehiculos de un conductor
         */
        Route::get("vehicles", [DriverUserVehicleController::class, 'index']);
        Route::post("vehicles", [DriverUserVehicleController::class, 'store']);
        Route::post("vehicles/edit_vehicle", [DriverUserVehicleController::class, 'update2']);
        //Route::put("vehicles/{id}", [DriverUserVehicleController::class, 'update']);
        Route::put("vehicles/{id}", [DriverUserVehicleController::class, 'update'])->middleware("has_reserved_service");
        Route::delete("vehicles/{id}", [DriverUserVehicleController::class, 'destroy']);
        /**
         * Despliegue datos de marcas y tipos de transporte
         */
        Route::get("transport_types", [DriverVehiclesController::class, 'index']);
        Route::get("marks", [DriverVehiclesController::class, 'index2']);

        /**
         * Despliegue y actualizacion de datos de cuenta de un conductor y sus transacciones.
         */
        Route::get("account", [UserBankAccount::class, 'index']);
        Route::post("account", [UserBankAccount::class, 'store']);
        Route::post("create_customer", [UserPaymentController::class, 'create']);
        Route::put("payment/{id}", [UserPaymentController::class, 'update']);
        Route::get("payment/{id}", [UserPaymentController::class, 'show']);
        Route::get("payment", [UserPaymentController::class, 'index']);
        Route::get("deposit_response/{id}/{response}", [UserPaymentController::class, 'depositResponse']);
        Route::get("payout_history", [UserPayoutController::class, 'index']);
        Route::get("payout/{id}", [UserPayoutController::class, 'show']);
        Route::put("payout/{id}", [UserPayoutController::class, 'update']);

        /**
         * Despliegue y actualizacion de estados de un conductor
         */
        Route::get("notifications", [ServiceStatusController::class, 'index']);
        Route::put("notifications/{id}", [ServiceStatusController::class, 'update']);
        
   
    });
});
