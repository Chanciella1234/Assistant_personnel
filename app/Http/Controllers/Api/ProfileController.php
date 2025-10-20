<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str; // Ajouté pour générer le token
use Illuminate\Support\Facades\Mail; // Pour la simulation d'envoi

class ProfileController extends Controller
{
    /**
     * 🟢 Afficher le profil utilisateur connecté
     */
    public function show()
    {
        $user = Auth::user();

        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            // Affiche l'email en attente s'il y en a un
            'pending_email' => $user->pending_email ?? null,
            'langue' => $user->langue,
            'theme' => $user->theme,
        ]);
    }

    /**
     * 🟢 Mettre à jour les informations du profil
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // Validation des champs
        $request->validate([
            'name' => 'nullable|string|max:255',
            // Ancien 'email' renommé en 'new_email'
            // L'unicité est vérifiée avant de commencer le processus de vérification
            'new_email' => 'nullable|email|unique:users,email,' . $user->id,
            'old_password' => 'nullable|string',
            'new_password' => 'nullable|string|min:6|confirmed',
            'langue' => 'nullable|in:fr,en,es',
            'theme' => 'nullable|in:clair,sombre',
        ]);

        $message = 'Profil mis à jour avec succès';
        $verification_link = null;

        // 🟣 Mettre à jour le nom
        if ($request->filled('name')) {
            $user->name = $request->name;
        }

        // 🟣 GESTION DU CHANGEMENT D'EMAIL SÉCURISÉ
        if ($request->filled('new_email')) {
            
            // 1. Stocker le nouvel email dans une colonne temporaire
            $user->pending_email = $request->new_email;
            
            // 2. Créer et stocker un token unique
            $token = Str::random(60);
            $user->email_verification_token = $token; 
            
            // 3. Simuler l'envoi de l'email de vérification
            // Le lien réel doit pointer vers votre API avec le token
            $verification_link = url('/api/profile/verify-email?token=' . $token);
            
            // Mail::to($request->new_email)->send(new VerifyNewEmail($token));

            $message = 'Un email de vérification a été envoyé à ' . $request->new_email . '. Veuillez confirmer le changement.';
        }

        // 🟣 Mettre à jour le mot de passe (avec vérification de l’ancien)
        if ($request->filled('old_password') || $request->filled('new_password')) {
            // ... (logique de vérification du mot de passe inchangée)
            if (!$request->filled('old_password') || !$request->filled('new_password')) {
                throw ValidationException::withMessages([
                    'password' => 'Vous devez fournir l’ancien et le nouveau mot de passe.',
                ]);
            }

            if (!Hash::check($request->old_password, $user->password)) {
                throw ValidationException::withMessages([
                    'old_password' => 'L’ancien mot de passe est incorrect.',
                ]);
            }
            
            $user->password = Hash::make($request->new_password);
        }

        // 🟣 Mettre à jour la langue
        if ($request->filled('langue')) {
            $user->langue = $request->langue;
        }

        // 🟣 Mettre à jour le thème
        if ($request->filled('theme')) {
            $user->theme = $request->theme;
        }

        $user->save();

        return response()->json([
            'message' => $message,
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'pending_email' => $user->pending_email, // Affichage du nouvel email en attente
                'langue' => $user->langue,
                'theme' => $user->theme,
            ],
            // Utile pour le débogage/test Postman
            'debug_link' => $verification_link
        ]);
    }

    // ----------------------------------------------------------------------

    /**
     * 🟣 NOUVELLE MÉTHODE : Confirmer la vérification du nouvel email via un token
     * La route API (ex: GET /api/profile/verify-email) doit pointer ici.
     */
    public function verifyNewEmail(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);
        
        $user = Auth::user(); // Supposons que l'utilisateur est authentifié pour l'appel
        
        // 1. Vérifier si le token correspond et si un email est en attente
        if (!$user || $user->email_verification_token !== $request->token || is_null($user->pending_email)) {
             return response()->json([
                 'message' => 'Lien de vérification invalide ou expiré.'
             ], 400);
        }

        // 2. Appliquer le changement d'email
        $user->email = $user->pending_email;
        
        // 3. Nettoyer les champs temporaires
        $user->pending_email = null;
        $user->email_verification_token = null;
        
        $user->save();

        return response()->json([
            'message' => 'Votre adresse email a été mise à jour avec succès.',
            'new_email' => $user->email
        ]);
    }
    
    // ----------------------------------------------------------------------
    
    /**
     * 🔴 Supprimer le compte utilisateur connecté avec confirmation
     */
    public function destroy(Request $request)
    {
        $user = Auth::user();
        // ... (Logique inchangée)
        $request->validate([
            'confirmation' => 'required|boolean'
        ]);

        if ($request->confirmation !== true) {
            return response()->json([
                'message' => 'Suppression annulée par l’utilisateur.'
            ], 400);
        }

        $user->delete();

        return response()->json([
            'message' => 'Compte supprimé avec succès'
        ]);
    }
}