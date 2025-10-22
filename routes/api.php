<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ActiviteController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TacheController;
use App\Http\Controllers\Api\StatistiquesController;

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




// // User profile routes
// Route::middleware('auth:sanctum')->group(function () {
    
//     // Afficher le profil
//     Route::get('/profile', [ProfileController::class, 'show']);
    
//     // Mettre à jour le profil (y compris le début du processus d'email)
//     Route::put('/profile', [ProfileController::class, 'update']);
    
//     // Supprimer le profil
//     Route::delete('/profile', [ProfileController::class, 'destroy']);

//     // Route DÉDIÉE pour la vérification de l'email
// // Elle est souvent en GET car le lien d'email est un GET.
// Route::get('/profile/verify-email', [ProfileController::class, 'verifyNewEmail']);
    
// });


// //alert routes
// Route::middleware('auth:sanctum')->group(function () {
//     Route::get('/alertes', [AlerteController::class, 'index']);
//     Route::get('/alertes/{id}', [AlerteController::class, 'show']);
//     Route::delete('/alertes/{id}', [AlerteController::class, 'destroy']);
// });



