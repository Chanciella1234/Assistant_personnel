<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordResetController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']); // crée le compte et envoie le code
    Route::post('/verify-code', [AuthController::class, 'verifyCode']); // vérifie le code à 6 chiffres
    Route::post('/resend-code', [AuthController::class, 'resendCode']); // renvoie le code d'activation
    Route::post('/login', [AuthController::class, 'login']);
});


Route::prefix('auth')->group(function () {
    Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']); //mot de passe oublie
    Route::post('/verify-reset-code', [PasswordResetController::class, 'verifyResetCode']); //verifie le code 
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']); //reinitialise le mot de passe
});



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});






