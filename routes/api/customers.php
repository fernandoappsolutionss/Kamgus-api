<?php

use App\Http\Controllers\HelpController;
use App\Http\Controllers\NewPasswordController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\V2\Customers\ArticlesController;
use App\Http\Controllers\V2\Customers\UserController as CustomersUserController;
use App\Http\Controllers\V2\Customers\ServiceController as CustomersServiceController;
use App\Http\Controllers\V2\Customers\ServiceStatusController;
use App\Http\Controllers\V2\Customers\UserPaymentController;
use App\Http\Controllers\V2\Customers\VehicleController as CustomerUserVehicleController;

Route::prefix('v2')->group(function () {
    Route::post('login/customer', [CustomersUserController::class, 'store']);
    Route::post('login/customer/{id}', [CustomersUserController::class, 'socialLogin']);
    Route::get('forgot/customer/{id}', [CustomersUserController::class, 'show']);
    Route::get('customer/articles', [ArticlesController::class, 'index']);
    Route::get('customer/articles/{id}', [ArticlesController::class, 'show']);
    Route::get('customer/article/{id}', [ArticlesController::class, 'show2']);
    Route::get('customer/article/search/{id}', [ArticlesController::class, 'search']);
    Route::get('validate_code', [CustomersUserController::class, 'validateCode']);
    Route::get('customer/vehicles', [CustomerUserVehicleController::class, 'index']);
    Route::get('customer/help', [HelpController::class, 'customers']);
    Route::get('customer/delete/{emailToken}', [CustomersUserController::class, 'deleteUser']);
    Route::get('customer/delete_account/{email}', [CustomersUserController::class, 'sendDeleteAccountConfirmation']);
    Route::post('tokenFCM', [CustomersUserController::class, 'updateFcmToken']);
    
    
    Route::post('customer/test_validation', [CustomersServiceController::class, 'testValidation']);

    Route::group([
        'middleware' => 'auth:api',
        'prefix' => "customer"
    ], function() {
        Route::post('fcm_token', [CustomersUserController::class, 'updateFcmToken']);

        Route::get('profile', [CustomersUserController::class, 'index']);
        Route::put('profile/{id}', [CustomersUserController::class, 'update']);
        Route::get("services", [CustomersServiceController::class, 'index']);
        Route::get("services/{id}", [CustomersServiceController::class, 'show']);
        Route::put("services/{id}", [CustomersServiceController::class, 'update']);
        Route::post("services", [CustomersServiceController::class, 'store']);
        Route::post("vehicles", [CustomerUserVehicleController::class, 'store']);
        Route::get('notifications', [ServiceStatusController::class, 'index']);
        Route::put('notifications/{id}', [ServiceStatusController::class, 'update']);
        Route::get('payments', [UserPaymentController::class, 'index']);
        Route::post('payments/create', [UserPaymentController::class, 'create']);
        Route::post('payments/{id}', [UserPaymentController::class, 'show']);
        Route::put('payments/{id}', [UserPaymentController::class, 'update']);
        Route::delete('payments/{id}', [UserPaymentController::class, 'destroy']); //Pendiente
        //Test services
        Route::get("notify_driver/{driverId}/{serviceId}", [CustomersServiceController::class, 'testNotifyDriver']);
        //Route::get("notify_driver/{driverId}/{serviceId}", [CustomersServiceController::class, 'getServiceDriversTokenExcept']);
    });
});