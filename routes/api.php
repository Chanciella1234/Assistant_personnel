<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\ProfileController;

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


// Activities and tasks routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('activities', App\Http\Controllers\Api\ActivityController::class);

    // Ajout routes spécifiques pour pause / reprise
    Route::patch('activities/{id}/pause', [App\Http\Controllers\Api\ActivityController::class, 'pause']);
    Route::patch('activities/{id}/resume', [App\Http\Controllers\Api\ActivityController::class, 'resume']);

    // API Resource imbriquée pour les tâches (CRUD standard : index, store, show, update, destroy)
    Route::apiResource('activities.tasks', App\Http\Controllers\Api\TaskController::class);

    // Routes spécifiques pour la gestion de la pause/reprise d'une tâche
    Route::patch('activities/{activity}/tasks/{task}/pause', [App\Http\Controllers\Api\TaskController::class, 'pause']);
    Route::patch('activities/{activity}/tasks/{task}/resume', [App\Http\Controllers\Api\TaskController::class, 'resume']);
});


// User profile routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Afficher le profil
    Route::get('/profile', [ProfileController::class, 'show']);
    
    // Mettre à jour le profil (y compris le début du processus d'email)
    Route::put('/profile', [ProfileController::class, 'update']);
    
    // Supprimer le profil
    Route::delete('/profile', [ProfileController::class, 'destroy']);

    // Route DÉDIÉE pour la vérification de l'email
// Elle est souvent en GET car le lien d'email est un GET.
Route::get('/profile/verify-email', [ProfileController::class, 'verifyNewEmail']);
    
});

