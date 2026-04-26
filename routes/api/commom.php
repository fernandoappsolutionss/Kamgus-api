<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TypeTransportController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\V2\WebApp\VehiclesModelController;

// use App\Http\Controllers\CountryController;

Route::prefix('v2')->group(function () {

    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('subcategories', [SubCategoryController::class, 'index']);
    Route::get('categories/{id}/subcategories', [CategoryController::class, 'subcategoriesinonecategory']);
    Route::get('categories/{category}/subcategories/{subcategory}/articles', [CategoryController::class, 'articlesinonesubcategory']);
    Route::get('typestransports', [TypeTransportController::class, 'index']);
    Route::get('typestransports/{id}', [TypeTransportController::class, 'show']);
    Route::get('articles', [ArticleController::class, 'index']);
    
    Route::get('articles/search/{article?}', [ArticleController::class, 'search']);
    
    Route::resource('countries', CountryController::class);
    //Route::get('tracks', CountryController::class);
    
    Route::get('vehicle_models', [VehiclesModelController::class, 'index']);
});