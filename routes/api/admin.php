<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\V2\WebApp\LicensesController;
use App\Http\Controllers\V2\WebApp\PaymentMController;
use App\Http\Controllers\V2\WebApp\ServicesController;
use App\Http\Controllers\V2\WebApp\UsersController;
use App\Http\Controllers\V2\WebApp\SupportController;
use App\Http\Controllers\V2\WebApp\RolesController;

//rutas del administrador
Route::group(['prefix' => 'v2', 'middleware' => 'auth:api'], function () {

    //rutas preparadas para el administrador
    Route::group(['middleware' => ['role:Administrador']], function () {
        
        //listar todos los usuarios
        Route::get('users', [UserController::class, 'index']);
        //Listar usuarios con paginacion
        Route::match(['get', 'post'], 'users/index', [UsersController::class, 'index']); 
        //Consultar informacion de un usuario especificado
        Route::get('users/{id}', [UsersController::class, 'show']);
        //Consultar info de un usuario y sus vehiculos registrados
        Route::post('users_id', [UsersController::class, 'getUserById']);
        //registrar un usuario - register an user
        Route::post('users/create', [UsersController::class, 'store']);
        //Buscar usuarios por su nombre, apellido o numero de telefono
        Route::post('users/search/{id?}', [UsersController::class, 'search']);
        //Buscar usuarios que se han registrado en un periodo de tiempo especificado
        Route::post('users/search_date', [UsersController::class, 'searchByDate']);
        //Actualizar informacion de un usuario especificado
        Route::put('users/{id}', [UsersController::class, 'update']);
        Route::post('users/{id}', [UsersController::class, 'updateImage']);
        //crear articulos
        Route::post('articles', [ArticleController::class, 'store']);
        //listar - actualizar - agregar - eliminar - mostrar roles
        Route::resource('roles', "V2\WebApp\RolesController");

        Route::group(['prefix' => 'admin', 'middleware' => 'auth:api'], function () {
            Route::put('users/{id}', [UsersController::class, 'update']);    
            
            //listar - actualizar - agregar - eliminar - mostrar licencias
            Route::resource("licenses", "V2\WebApp\LicensesController");
            //Devuelve el historial de servicios
            Route::get("services", [ServicesController::class, "index"]);
            Route::put("services/{id}", [ServicesController::class, "update"]);
    
            //listar - actualizar - agregar - eliminar - mostrar configuraciones
            Route::resource("settings", "V2\WebApp\SettingsController");
    
            //listar - actualizar - agregar - eliminar - mostrar payouts
            Route::get("payouts/states", [PaymentMController::class, "getTransacionStates"]);
            Route::get("payouts/pending", [PaymentMController::class, "pendingDrivers"]);
            Route::resource("payouts", "V2\WebApp\PaymentMController");
            Route::get("payouts/customers/{id}", [PaymentMController::class, "showCustomer"]);
            Route::get("payouts/pending/{id}", [PaymentMController::class, "showPendingDriverPayments"]);

            //conteo de usuarios, servicos activos y pagos de conductor
            Route::get("count/drivers/actived", [UsersController::class, 'getCountDrivers']);
            Route::get("count/services/actived", [ServicesController::class, 'getCountServices']);
        });
        


    });
    
    //Support services 
    Route::resource("support", "V2\WebApp\SupportController");
    
});
Route::get("v2/licenses", [LicensesController::class, "index"]);