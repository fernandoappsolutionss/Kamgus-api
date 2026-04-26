<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LicenseController;

Route::group(['prefix' => 'v2', 'middleware' => 'auth:api'], function () {

    //mostrar licencia de la empresa autenticada
    Route::get('license', [LicenseController::class, 'my_license']);

    //modulos de la licencia del usuario
    Route::get('license/modules', [LicenseController::class, 'license_modules']);

});

