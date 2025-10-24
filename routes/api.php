<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ActiviteController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TacheController;
use App\Http\Controllers\Api\StatistiquesController;
use App\Http\Controllers\Api\CommentaireController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminStatistiquesController;
use App\Http\Controllers\Api\AlerteController;


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


// Activite routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/activites', [ActiviteController::class, 'index']);
    Route::post('/activites', [ActiviteController::class, 'store']);
    Route::get('/activites/{id}', [ActiviteController::class, 'show']);
    Route::put('/activites/{id}', [ActiviteController::class, 'update']);
    Route::delete('/activites/{id}', [ActiviteController::class, 'destroy']);

    // Pause / reprise
    Route::put('/activites/{id}/pause', [ActiviteController::class, 'pause']);
    Route::put('/activites/{id}/reprendre', [ActiviteController::class, 'reprendre']);
});


//Tache routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/activites/{activite_id}/taches', [TacheController::class, 'index']);
    Route::post('/activites/{activite_id}/taches', [TacheController::class, 'store']);
    Route::put('/activites/{activite_id}/taches/{tache_id}', [TacheController::class, 'update']);
    Route::delete('/activites/{activite_id}/taches/{tache_id}', [TacheController::class, 'destroy']);

    // Pause / reprise
    Route::put('/activites/{activite_id}/taches/{tache_id}/pause', [TacheController::class, 'pause']);
    Route::put('/activites/{activite_id}/taches/{tache_id}/reprendre', [TacheController::class, 'reprendre']);
});


//Statistiques routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/statistiques/activites', [StatistiquesController::class, 'index']);
});

//Commentaires routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/commentaires', [CommentaireController::class, 'index']);
    Route::post('/commentaires', [CommentaireController::class, 'store']);
    Route::put('/commentaires/{id}', [CommentaireController::class, 'update']);
    Route::delete('/commentaires/{id}', [CommentaireController::class, 'destroy']);
});


//Admin routes
Route::middleware('auth:sanctum')->group(function () {
    // 🟢 Routes Admin (lecture seule)
    Route::get('/admin/utilisateurs', [AdminController::class, 'index']);
    Route::get('/admin/utilisateurs/{id}', [AdminController::class, 'show']);
});


//Statistiques pour Admin routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admin/statistiques', [AdminStatistiquesController::class, 'index']);
});


//Alertes routes
Route::middleware('auth:sanctum')->group(function () {
    // 🔔 Alertes personnalisées
    Route::get('/activites/{id}/rappel', [AlerteController::class, 'show']);
    Route::post('/activites/{id}/rappel', [AlerteController::class, 'setRappel']);
    Route::delete('/activites/{id}/rappel', [AlerteController::class, 'deleteRappel']);
});


//User Profile routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    
    // 🔹 Changement d’e-mail avec code OTP
    Route::post('/profile/request-email-change', [ProfileController::class, 'requestEmailChange']);
    Route::post('/profile/verify-email-change', [ProfileController::class, 'verifyEmailChange']);
    
    Route::delete('/profile', [ProfileController::class, 'destroy']);
});






