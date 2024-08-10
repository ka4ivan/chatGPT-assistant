<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

require __DIR__.'/api-auth.php';

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/ask/assistant', [\App\Http\Client\Controllers\AiController::class, 'askAssistant'])->middleware('auth:sanctum');
