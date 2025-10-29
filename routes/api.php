<?php

use App\Http\Controllers\CountryController;
use Illuminate\Support\Facades\Route;

Route::get('/countries/image', [CountryController::class, 'image']);
Route::post('/countries/refresh', [CountryController::class, 'refresh']);
Route::get('/countries', [CountryController::class, 'index']);
Route::get('/countries/{name}', [CountryController::class, 'show']);
Route::delete('/countries/{name}', [CountryController::class, 'destroy']);
Route::get('/status', [CountryController::class, 'status']);
