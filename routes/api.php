<?php

use App\Http\Controllers\RestaurantController;
use Illuminate\Support\Facades\Route;

Route::controller(RestaurantController::class)->group(function () {
    Route::get('/restaurants', 'index');
    Route::get('/restaurants/{restaurant}', 'show');
    Route::post('/restaurants/import', 'import');
});
