<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ServiceController;

Route::group(['prefix' => 'v2'], function () {
    Route::get('stripe/redirected', [PaymentController::class, 'stripeRedirected']);

});
Route::group(['prefix' => 'v2', 'middleware' => 'auth:api'], function () {

    //retornar métodos de pago agregados por el cliente
    Route::get('payment/methods', [PaymentController::class, 'own_payment_methods']);
   
     //añadir metodo de pago para el cliente autenticado
    Route::post('new/payment/method', [PaymentController::class, 'add_new_payment_method']);
    Route::post('remove/payment/method', [PaymentController::class, 'destroy']);
    Route::delete('remove/payment/method/{id}', [PaymentController::class, 'destroy']);
    Route::post('setup/intent', [PaymentController::class, 'add_payment_method']);

    //ver el balance del usuario autenticado
    Route::get('balance', [PaymentController::class, 'balance']);

    //listar todas las facturas del usuario autenticado
    Route::get('invoinces', [PaymentController::class, 'invoinces']);
    
    //pagar un servicio
    Route::post('pay/service', [PaymentController::class, 'pay_service']);


    //prueba notificación a conductores
    Route::post('prueba/notificacion', [ServiceController::class, 'notify_drivers']);

});

