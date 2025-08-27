<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmpresaController;
use App\Http\Controllers\Api\JsonController;
use App\Http\Controllers\Api\SC5010Controller;
use App\Http\Controllers\Api\SC6010Controller;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\DA0DatateController;
use App\Http\Controllers\ProcessOrderController;
use App\Http\Controllers\SB1Controller;
use Illuminate\Support\Facades\Route;

Route::post('create-order', [ProcessOrderController::class, 'createOrder']);
Route::put('/update-order/{orderNumber}', [ProcessOrderController::class, 'updateOrder']);
Route::put('/update-order-item/{orderNumber}/{item}', [ProcessOrderController::class, 'updateOrderItem']);
//tablas consultas
Route::get('/json/{nombre}', [JsonController::class, 'show']);
Route::put('/json/{nombre}', [JsonController::class, 'update']);
Route::post('/buscar-codigo', [SB1Controller::class, 'buscarCodigo']);

//tablas API
Route::get('da0_datate', [DA0DatateController::class, 'index']);
Route::apiResource('sc5010', SC5010Controller::class);
Route::apiResource('sc6010', SC6010Controller::class);
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
//tablas verificadas
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

Route::get('/empresas', [EmpresaController::class, 'index']);
