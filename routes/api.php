<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ActivityController;

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']); // crée le compte et envoie le code
    Route::post('/verify-code', [AuthController::class, 'verifyCode']); // vérifie le code à 6 chiffres
    Route::post('/resend-code', [AuthController::class, 'resendCode']); // renvoie le code d'activation
    Route::post('/login', [AuthController::class, 'login']); // connexion
});

// Password reset routes
Route::prefix('auth')->group(function () {
    Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']); //mot de passe oublie
    Route::post('/verify-reset-code', [PasswordResetController::class, 'verifyResetCode']); //verifie le code de reinitialisation
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']); //reinitialise le mot de passe
});


// Activities routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('activities', App\Http\Controllers\Api\ActivityController::class);

    // Ajout routes spécifiques pour pause / reprise
    Route::patch('activities/{id}/pause', [App\Http\Controllers\Api\ActivityController::class, 'pause']);
    Route::patch('activities/{id}/resume', [App\Http\Controllers\Api\ActivityController::class, 'resume']);
});








