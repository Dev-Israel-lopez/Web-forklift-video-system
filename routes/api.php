<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EventController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Login (sin middleware)
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

// Events (autenticación se maneja en el controlador con Bearer + X-User-Id)
Route::post('/events', [EventController::class, 'store'])->name('api.events.store');
Route::get('/events', [EventController::class, 'index']);

