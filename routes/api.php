<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;


// Rutas públicas
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Rutas protegidas
Route::middleware('auth:api')->group(function () {
    Route::get('profile', [AuthController::class, 'profile']);
    Route::put('profile', [AuthController::class, 'updateProfile']);
    Route::post('register-driver', [AuthController::class, 'registerDriver']);
    Route::post('logout', [AuthController::class, 'logout']);
});
