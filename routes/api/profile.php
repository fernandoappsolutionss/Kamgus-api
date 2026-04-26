<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;

Route::group(['prefix' => 'v2', 'middleware' => 'auth:api'], function () {

    //mostrar usuario autenticado
    Route::get('user', [UserController::class, 'show']);

    //actualizar cualquier tipo de perfil de usuario (empresa, conductor, cliente)
    Route::put('update/user/profile', [ProfileController::class, 'updateanyprofile']);
    Route::patch('update/user/profile', [ProfileController::class, 'updateanyprofile']);

    //actualizar imagen de cualquier perfil
    Route::post('update/user/profile/image', [ProfileController::class, 'updateanyprofileimg']);


});
