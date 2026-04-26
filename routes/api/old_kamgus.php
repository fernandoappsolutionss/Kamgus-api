<?php

use App\Classes\K_HelpersV1;
use App\Classes\K_MigrateDBV1ToV2Class;
use App\Http\Controllers\Dashboard_V1\ServiceController;
use App\Http\Controllers\Dashboard_V1\TransactionController;
use App\Http\Controllers\NewPasswordController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::prefix('v2')->group(function () {
    Route::group([
        //'middleware' => 'auth:api',
        'prefix' => "migrate_v1"
    ], function() {
        Route::get("driver_vehicles", function(){
            return K_MigrateDBV1ToV2Class::getInstance()->migrateDriverVehicles();
        });
        Route::get("refresh_vehicles", function(){
            return K_MigrateDBV1ToV2Class::getInstance()->refreshVehicles();
        });
        Route::get("services", function(){
            return K_MigrateDBV1ToV2Class::getInstance()->migrateServices();
        });
        Route::get("services_tt", function(){
            return K_MigrateDBV1ToV2Class::getInstance()->migrateServicesTT();
        });
        Route::get("service_images", function(){
            return K_MigrateDBV1ToV2Class::getInstance()->migrateImagesServices();
        });
        Route::get("service_transaction", function(){
            return K_MigrateDBV1ToV2Class::getInstance()->migrateTransactionServices();
        });
        Route::get("service_qualifications", function(){
            return K_MigrateDBV1ToV2Class::getInstance()->migrateQualifications();
        });
        Route::get("service_states", function(){
            return K_MigrateDBV1ToV2Class::getInstance()->migrateServiceStates();
        });
        Route::get("service_paid", function(){
            return K_MigrateDBV1ToV2Class::getInstance()->refreshPaidFieldInService();
        });
        Route::get("driver_balance", function(){
            return K_MigrateDBV1ToV2Class::getInstance()->migrateBalanceTransactions();
        });
        Route::get("driver_accounts", function(){
            return K_MigrateDBV1ToV2Class::getInstance()->migrateDriverAccounts();
        });
        Route::get("driver_services", function(){
            return K_MigrateDBV1ToV2Class::getInstance()->refreshDriverService();
        });
        Route::get("user_bonus", function(){
            return K_MigrateDBV1ToV2Class::getInstance()->migrateUserBonus();
        });
        Route::get("user_photo", function(){
            return K_MigrateDBV1ToV2Class::getInstance()->migrateUserPhotos();
        });
        Route::get("move_images", function(){
            return K_MigrateDBV1ToV2Class::getInstance()->migrateImageLocation();
        });
        Route::get("user_date", function(){
            return K_MigrateDBV1ToV2Class::getInstance()->refreshCreatedAtUser();
        });
        Route::get("company_emp", function(){
            return K_MigrateDBV1ToV2Class::getInstance()->moveCompanyToCustomer();
        });
    });
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
    Route::get("test", function(){
        K_HelpersV1::getInstance()->confirmDriver([
            "servicio_id" => "7579",        
            "driver_id" => "15646",], 2871);
    });

}); 
