<?php

use Illuminate\Support\Facades\Route;

Route::post('register', [\App\Http\Auth\Controllers\RegisterController::class, 'register']);
Route::post('login', [\App\Http\Auth\Controllers\LoginController::class, 'login']);
Route::post('logout', [\App\Http\Auth\Controllers\LoginController::class, 'logout']);
