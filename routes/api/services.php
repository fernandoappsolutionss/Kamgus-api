<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\Dashboard\ServiceController as DashboardServiceController;

Route::get('drivers_availables', [DashboardServiceController::class, 'getAvailableDrivers']);
Route::group(['prefix' => 'v2', 'middleware' => 'auth:api'], function () {

    //cotizar servicios por vehiculos
    Route::post('servicesxvehicles', [ServiceController::class, 'servicesxvehicles']);

    //cotizar servicios por articulos
    Route::post('servicesxarticles', [ServiceController::class, 'servicesxarticles']);

    //buscar un servicio por su id
    Route::get('services/{id}', [ServiceController::class, 'show']);

    //ruta para cancelar un servicio
    Route::post('cancel/service', [ServiceController::class, 'cancel_service']);

    //listar los servicios activos del usuario autenticado
    Route::get('own/active/services', [ServiceController::class, 'own_active_services']);

    //listar los servicios programados del usuario autenticado
    Route::get('own/scheduled/services', [ServiceController::class, 'own_scheduled_services']);

    //listar todo el historial de servicios del usuario autenticado
    Route::get('own/historical/services', [ServiceController::class, 'own_historical_services']);

    //filtrar historial de servicios
    Route::get('search/service/history/{initial_date?}/{final_date?}/{status?}', [ServiceController::class, 'search_service_history']);
    
    //
    Route::get('drivers_availables', [DashboardServiceController::class, 'getAvailableDrivers2']);

});

//recibidor de ventos webhooks de stripe
Route::post('stripe/webhook', [Laravel\Cashier\Http\Controllers\WebhookController::class, 'handleWebhook'])->prefix('v2');
