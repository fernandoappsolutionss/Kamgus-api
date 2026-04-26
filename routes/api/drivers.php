<?php

use App\Classes\K_HelpersV1;
use App\Classes\StripeCustomClass;
use App\Http\Controllers\NewPasswordController;
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
    /**
     * Servicios para testing
     */
    Route::get('/old', [UserController::class, "users_old_db"]);
    Route::get('/db/{id}', function($id){ //test
        $userBalance = calculateDriverBalance($id, DB::table("transactions"));
        return "<br>".K_HelpersV1::getInstance()->calculateDriverBalance($id)." V2: ".$userBalance;
    });
    Route::get('/balances', function(){ //test
        //return StripeCustomClass::getInstance()->getBalanceTransactions();
    });
    Route::get('/finish_service', function(){ //test
        K_HelpersV1::getInstance()->setServRecibido(array_merge(["punto" => "B", "nombre_recep" => "test", "identidad_recep" => "6789",], ["service_id" => 7931]), 2871);
    });

    Route::get("driver/test_timezone", function(){
        $date = new DateTime( "2023-09-24 18:00:00" );
        $fechNueva =  $date->format('Y-m-d H:i:s');
        //return App\Models\User::find(2871)->userable->nombres;
		$response = array(
			'error' => false, 
			'msg' => date_default_timezone_get(), 
			"time" => date("Y-m-d H:i:s"),
			"fech" => $fechNueva,
		);
        return response()->json($response);
    });
    Route::get("driver/notify/{id}", function($id){
        $user = App\Models\User::find($id);
        $fcmTokens = $user->fcmtokens()->orderBy("updated_at", "DESC")->get();
        //dd();
        $array = $fcmTokens->pluck("token")->toArray();
        $response = $user->notify(new App\Notifications\K_SendPushNotification("Prueba", "Mensaje de prueba", $array,["url" => "driver-confirmation"]));
        //$response = notifyToCustomer($user, "Prueba", "Mensaje de prueba", ["url" => "driver-confirmation"]);
        return response()->json(["error" => false, "msg" => "Notificacion enviada", "response" => $response]);
    });

});