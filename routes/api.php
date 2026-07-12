<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V2\QuoteController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('/v2', function (Request $request) {
    return "APIkamgus";
});

Route::post('/v2/quote', [QuoteController::class, 'store'])->middleware('throttle:30,1');
