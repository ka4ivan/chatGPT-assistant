<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

require __DIR__.'/api-auth.php';

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/ask/assistant', [\App\Http\Client\Controllers\AiController::class, 'askAssistant'])->middleware('auth:sanctum');
Route::post('/ask/assistant/image', [\App\Http\Client\Controllers\AiController::class, 'askAssistantWithImage'])->middleware('auth:sanctum');
