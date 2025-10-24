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
        $user->email_verification_expires_at = Carbon::now()->addMinutes(15);
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
