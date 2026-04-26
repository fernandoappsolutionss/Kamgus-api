<?php

use App\Http\Controllers\NewPasswordController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\V2\Drivers\UserController as DriversUserController;
use App\Models\User;

Route::prefix('v2')->group(function () {
    // Registro de usuarios
    Route::post('registration/customers', [UserController::class, 'registration_customers']);
    Route::post('registration/drivers', [UserController::class, 'registration_drivers']);
    Route::post('registration/companies', [UserController::class, 'registration_companies']);

    Route::post('verification/email', [UserController::class, 'verificacion_correo']);

    Route::get('forgot/password', [NewPasswordController::class, 'forgot']);
    Route::post('reset/password', [NewPasswordController::class, 'reset']);

    Route::post('login', [UserController::class, 'login']);

    //v1
    Route::post('login_gt', [UserController::class, 'login']);
    Route::put('change/{id}', [UserController::class, 'update']);
    Route::delete('delete/{id}', [UserController::class, 'destroy']);
    Route::get('balance/{id}', function($id){
        $user = User::find($id);
        $balance = calculateDriverBalance($user->id, DB::table("transactions"));
        return $balance ? $balance : "vacio";
    });
    


});