<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmpresaController;
use App\Http\Controllers\Api\JsonController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\SB1Controller;
use Illuminate\Support\Facades\Route;

Route::get('/json/{nombre}', [JsonController::class, 'show']);
Route::put('/json/{nombre}', [JsonController::class, 'update']);

Route::post('/buscar-codigo', [SB1Controller::class, 'buscarCodigo']);

Route::get('/users', [AuthController::class, 'index'])->middleware('auth:sanctum');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');
Route::get('/email/verify', [VerificationController::class, 'show'])
    ->middleware(['auth:sanctum'])
    ->name('verification.notice');
Route::post('/email/verification-notification', [VerificationController::class, 'resend'])
    ->middleware(['auth:sanctum', 'throttle:6,1'])
    ->name('verification.send');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/user', [AuthController::class, 'update']);
    Route::delete('/user', [AuthController::class, 'delete']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/empresas', [EmpresaController::class, 'store']);
    Route::get('/empresas/{id}', [EmpresaController::class, 'show']);
    Route::put('/empresas/{id}', [EmpresaController::class, 'update']);
    Route::delete('/empresas/{id}', [EmpresaController::class, 'destroy']);
});

// Ruta pública para listar todas las empresas
Route::get('/empresas', [EmpresaController::class, 'index']);
