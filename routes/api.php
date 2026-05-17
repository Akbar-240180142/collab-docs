<?php

use App\Http\Controllers\DocumentShareController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/documents/{document}/users', [DocumentShareController::class, 'index']);
    Route::post('/documents/{document}/users', [DocumentShareController::class, 'store']);
    Route::patch('/documents/{document}/users/{documentUser}', [DocumentShareController::class, 'update']);
    Route::delete('/documents/{document}/users/{documentUser}', [DocumentShareController::class, 'destroy']);
});