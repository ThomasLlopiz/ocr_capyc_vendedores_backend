<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarpetaEntradaController;
use App\Http\Controllers\Api\ConfiguracionOcrController;
use App\Http\Controllers\Api\EmpresaController;
use App\Http\Controllers\Api\EspacioTrabajoController;
use App\Http\Controllers\Api\JsonController;
use App\Http\Controllers\Api\SC5010Controller;
use App\Http\Controllers\Api\SC6010Controller;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\DA0DatateController;
use App\Http\Controllers\PrecioController;
use App\Http\Controllers\ProcessOrderController;
use App\Http\Controllers\SB1Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ============================================================
// PÚBLICAS
// ============================================================

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

Route::get('/health', fn() => response()->json(['message' => 'Bienvenido a la API']));

// ============================================================
// PROTEGIDAS
// ============================================================

Route::middleware('auth:sanctum')->group(function () {

    // --- Auth ---
    Route::get('/me', fn(Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- Users ---
    Route::get('/users', [AuthController::class, 'index']);
    Route::put('/user', [AuthController::class, 'update']);
    Route::delete('/user', [AuthController::class, 'delete']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // --- OCR: configuración ---
    Route::get('/ocr-configurations', [ConfiguracionOcrController::class, 'index']);
    Route::get('/ocr-configurations/formatos', [ConfiguracionOcrController::class, 'formatos']);
    Route::get('/ocr-configurations/totvs-companies', [ConfiguracionOcrController::class, 'buscarEmpresas']);
    Route::post('/ocr-configurations', [ConfiguracionOcrController::class, 'store']);
    Route::put('/ocr-configurations/{configuration}', [ConfiguracionOcrController::class, 'update']);
    Route::delete('/ocr-configurations/{configuration}', [ConfiguracionOcrController::class, 'destroy']);

    // --- OCR: carpeta de entrada ---
    Route::get('/input-pdfs', [CarpetaEntradaController::class, 'index']);
    Route::post('/input-pdfs/process', [CarpetaEntradaController::class, 'import']);

    // --- OCR: documentos ---
    Route::get('/documents', [EspacioTrabajoController::class, 'documents']);
    Route::post('/documents/accept-batch', [EspacioTrabajoController::class, 'acceptBatch']);
    Route::post('/documents/retry-batch', [EspacioTrabajoController::class, 'retryBatch']);
    Route::get('/documents/{document}', [EspacioTrabajoController::class, 'showDocument']);
    Route::put('/documents/{document}', [EspacioTrabajoController::class, 'saveDocument']);
    Route::put('/documents/{document}/seller-items', [EspacioTrabajoController::class, 'saveSellerItems']);
    Route::post('/documents/{document}/accept', [EspacioTrabajoController::class, 'accept']);
    Route::post('/documents/{document}/retry', [EspacioTrabajoController::class, 'retry']);
    Route::post('/documents/{document}/verify-products', [EspacioTrabajoController::class, 'verifyProducts']);
    Route::post('/documents/{document}/orders', [EspacioTrabajoController::class, 'createOrders']);
    Route::get('/documents/{document}/history', [EspacioTrabajoController::class, 'history']);
    Route::get('/documents/{document}/pdf', [EspacioTrabajoController::class, 'pdf']);
    Route::delete('/documents/{document}', [EspacioTrabajoController::class, 'deleteDocument']);
    Route::delete('/documents/{document}/extraction', [EspacioTrabajoController::class, 'deleteExtraction']);

    // --- TOTVS ---
    Route::get('/empresas', [EmpresaController::class, 'index']);
    Route::match(['get', 'post'], '/empresas/buscar', [EmpresaController::class, 'buscarCodigo']);
    Route::post('/empresas/buscar-nombre', [EmpresaController::class, 'buscarPorNombre']);
    Route::post('/buscar-codigo', [SB1Controller::class, 'buscarCodigo']);
    Route::post('/buscar-precio', [PrecioController::class, 'buscarPrecio']);
    Route::get('/da0_datate', [DA0DatateController::class, 'index']);

    // --- SC5/SC6 (para Ordenes.jsx) ---
    Route::post('/create-order', [ProcessOrderController::class, 'createOrder']);
    Route::put('/update-order/{orderNumber}', [ProcessOrderController::class, 'updateOrder']);
    Route::put('/update-order-item/{orderNumber}/{item}', [ProcessOrderController::class, 'updateOrderItem']);
    Route::apiResource('sc5010', SC5010Controller::class);
    Route::apiResource('sc6010', SC6010Controller::class);

    // --- JSON legacy ---
    Route::get('/json/{nombre}', [JsonController::class, 'show']);
    Route::put('/json/{nombre}', [JsonController::class, 'update']);
});