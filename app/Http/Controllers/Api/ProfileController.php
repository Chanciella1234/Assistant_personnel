<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use App\Mail\VerifyNewEmailCodeMail;
use Illuminate\Support\Str;

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
            'pending_email' => $user->pending_email,
            'langue' => $user->langue,
            'theme' => $user->theme,
        ]);
    }

    /**
     * 🟢 Mettre à jour le nom et/ou le mot de passe
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'nullable|string|max:255',
            'old_password' => 'nullable|string',
            'new_password' => 'nullable|string|min:6|confirmed',
        ]);

        // 🟣 Modifier le nom s’il est fourni
        if ($request->filled('name')) {
            $user->name = $request->name;
        }

        // 🟣 Changement du mot de passe
        if ($request->filled('old_password') || $request->filled('new_password')) {
            // Vérification des deux champs
            if (!$request->filled('old_password') || !$request->filled('new_password')) {
                throw ValidationException::withMessages([
                    'password' => 'Vous devez fournir l’ancien et le nouveau mot de passe.',
                ]);
            }

            // Vérification de l’ancien mot de passe
            if (!Hash::check($request->old_password, $user->password)) {
                throw ValidationException::withMessages([
                    'old_password' => 'L’ancien mot de passe est incorrect.',
                ]);
            }

            // Validation du nouveau mot de passe
            $user->password = Hash::make($request->new_password);
        }

        $user->save();

        return response()->json([
            'message' => 'Profil mis à jour avec succès.',
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * 🟢 Demande de changement d’e-mail — envoie un code de vérification
     */
    public function requestEmailChange(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'new_email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        // Générer un code à 6 chiffres
        $code = rand(100000, 999999);

        // Sauvegarder dans la base
        $user->pending_email = $request->new_email;
        $user->email_verification_code = $code;
        $user->email_verification_expires_at = Carbon::now()->addMinutes(30);
        $user->save();

        // Envoi du mail
        Mail::to($request->new_email)->send(new VerifyNewEmailCodeMail($user->name, $code));

        return response()->json([
            'message' => 'Un code de vérification a été envoyé à ' . $request->new_email,
        ]);
    }

    /**
     * 🟢 Vérifier le code et valider le changement d’e-mail
     */
    public function verifyEmailChange(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'code' => 'required|digits:6',
        ]);

        // Vérifier la correspondance du code
        if (
            $user->email_verification_code !== $request->code ||
            !$user->pending_email ||
            Carbon::now()->greaterThan($user->email_verification_expires_at)
        ) {
            throw ValidationException::withMessages([
                'code' => 'Code invalide ou expiré.',
            ]);
        }

        // ✅ Appliquer le changement
        $user->email = $user->pending_email;

        // Nettoyage
        $user->pending_email = null;
        $user->email_verification_code = null;
        $user->email_verification_expires_at = null;
        $user->save();

        return response()->json([
            'message' => 'Votre adresse e-mail a été mise à jour avec succès.',
            'email' => $user->email,
        ]);
    }

    /**
     * 🔴 Supprimer le compte utilisateur connecté
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'confirmation' => 'required|boolean'
        ]);

        $user = Auth::user();

        if ($request->confirmation !== true) {
            return response()->json(['message' => 'Suppression annulée.'], 400);
        }

        $user->delete();

        return response()->json(['message' => 'Compte supprimé avec succès.']);
    }
}
