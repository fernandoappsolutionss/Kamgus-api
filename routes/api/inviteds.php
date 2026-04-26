<?php

use App\Classes\StripeCustomClass;
use App\Http\Controllers\NewPasswordController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\V2\Inviteds\ArticlesController;
use App\Http\Controllers\V2\Inviteds\UserController as InvitedsUserController;
use App\Http\Controllers\V2\Inviteds\ServiceController as InvitedsServiceController;
use App\Http\Controllers\V2\Inviteds\ServicePaymentController as InvitedsServicePaymentController;
use App\Http\Controllers\V2\Inviteds\SSEvents\ServicesSSEController;
use App\Http\Controllers\V2\Inviteds\UserPaymentController;
use App\Http\Controllers\V2\Inviteds\UserVehicleController as DriverUserVehicleController;
use App\Http\Controllers\V2\Inviteds\VehicleController as InvitedsVehiclesController;
use App\Http\Controllers\V2\Inviteds\WebhooksController;

Route::prefix('v2')->group(function () {
    Route::get("invited/transaction_status/{id}", [InvitedsServicePaymentController::class, 'show']);
    Route::get('invited/articles', [ArticlesController::class, 'index']);
    Route::get('invited/articles/{id}', [ArticlesController::class, 'show']);
    Route::get('invited/article/{id}', [ArticlesController::class, 'show2']);
    Route::get('invited/article/search/{id}', [ArticlesController::class, 'search']);
    Route::match(['get', 'post'],'invited/wh/yappy_status', [WebhooksController::class, 'yappyStatus']);
    Route::match(['get', 'post'],'invited/wh/stripe_status', [WebhooksController::class, 'stripeStatus']);
    Route::match(['get', 'post'],'invited/wh/pago_cash_status', [WebhooksController::class, 'pagoCashStatus']);
    Route::group([
        //'middleware' => 'auth:api',
        'prefix' => "invited"
    ], function() {
        
        Route::get("services", [InvitedsServiceController::class, 'index']);
        Route::post("services", [InvitedsServiceController::class, 'store']);
        Route::get("services/{id}", [InvitedsServiceController::class, 'show']);
        Route::get("sse/service", [ServicesSSEController::class, 'suggestedPrices']);
        Route::put("services/{id}", [InvitedsServiceController::class, 'update']);
        Route::put("user/{id}", [InvitedsUserController::class, 'update']);
        
        Route::post("service_payment", [InvitedsServicePaymentController::class, 'store']);
        Route::get("service_payment/{id}", [InvitedsServicePaymentController::class, 'show']);
        Route::put("service_payment/{id}", [InvitedsServicePaymentController::class, 'update']);
        Route::get("vehicles", [InvitedsVehiclesController::class, 'index']);

        
//        Route::post("create_customer", [UserPaymentController::class, 'create']);
    });
    Route::get('/old/{id?}', [UserController::class, "users_old_db"]);
    Route::get('/invited/cs/{id}', function($id){
        define("STRIPE_SK_TEST", '***REMOVED-STRIPE-TEST***');
        return StripeCustomClass::getInstance()->getCheckoutSession($id);
    });
});