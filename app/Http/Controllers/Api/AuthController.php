<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ActivationMail;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    /**
     * @group Authentification
     *
     * Inscription (Envoi du code d’activation)
     *
     * Cette route permet à un utilisateur de s’inscrire.  
     * Un code d’activation de 6 chiffres sera envoyé par email avant l’activation du compte.
     *
     * @bodyParam name string required Nom complet de l’utilisateur. Exemple: Alice Dupont
     * @bodyParam email string required Adresse email valide et unique. Exemple: alice@example.com
     * @bodyParam password string required Mot de passe d’au moins 6 caractères. Exemple: secret123
     * @bodyParam password_confirmation string required Confirmation du mot de passe. Exemple: secret123
     *
     * @response 201 {
     *  "message": "Compte créé. Vérifiez votre e-mail pour le code d’activation.",
     *  "user": {
     *      "id": 1,
     *      "email": "alice@example.com"
     *  }
     * }
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $code = rand(100000, 999999);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'code_activation' => $code,
        ]);

        Mail::to($user->email)->send(new ActivationMail($user, $code));

        return response()->json([
            'message' => 'Compte créé. Vérifiez votre e-mail pour le code d’activation.',
            'user' => $user->only(['id', 'email']),
        ], 201);
    }

    /**
     * @group Authentification
     *
     * Vérification du code d’activation
     *
     * Cette route permet à l’utilisateur de valider le code reçu par e-mail.
     *
     * @bodyParam email string required Adresse email utilisée lors de l’inscription. Exemple: alice@example.com
     * @bodyParam code_activation string required Code à 6 chiffres reçu par e-mail. Exemple: 123456
     *
     * @response 200 {
     *  "message": "Compte activé avec succès. Vous pouvez maintenant vous connecter."
     * }
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code_activation' => 'required|digits:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->is_verified) {
            return response()->json(['message' => 'Compte déjà activé.'], 400);
        }

        if ($user->code_activation != $request->code_activation) {
            return response()->json(['message' => 'Code invalide.'], 400);
        }

        $user->update([
            'is_verified' => true,
            'code_activation' => null,
        ]);

        Mail::to($user->email)->send(new WelcomeMail($user));

        return response()->json(['message' => 'Compte activé avec succès. Vous pouvez maintenant vous connecter.']);
    }

    /**
     * @group Authentification
     *
     * Renvoyer un nouveau code d’activation
     *
     * Permet à un utilisateur non vérifié de recevoir un nouveau code d’activation.
     *
     * @bodyParam email string required Adresse email de l’utilisateur. Exemple: alice@example.com
     *
     * @response 200 {
     *  "message": "Nouveau code envoyé à votre adresse e-mail.",
     *  "email": "alice@example.com"
     * }
     */
    public function resendCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->is_verified) {
            return response()->json(['message' => 'Ce compte est déjà activé.'], 400);
        }

        $newCode = rand(100000, 999999);
        $user->update(['code_activation' => $newCode]);

        Mail::to($user->email)->send(new ActivationMail($user, $newCode));

        return response()->json([
            'message' => 'Nouveau code envoyé à votre adresse e-mail.',
            'email' => $user->email,
        ]);
    }

    /**
     * @group Authentification
     *
     * Connexion
     *
     * Cette route permet à un utilisateur activé de se connecter et de recevoir un jeton d’accès (token Bearer).
     *
     * @bodyParam email string required Adresse email de l’utilisateur. Exemple: alice@example.com
     * @bodyParam password string required Mot de passe de l’utilisateur. Exemple: secret123
     *
     * @response 200 {
     *  "message": "Connexion réussie",
     *  "access_token": "1|p6cjhD3t2hA2zP5y8kP6a...",
     *  "token_type": "Bearer",
     *  "user": {
     *      "id": 1,
     *      "name": "Alice Dupont",
     *      "email": "alice@example.com",
     *      "role": "client"
     *  }
     * }
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Identifiants invalides.'], 401);
        }

        if (!$user->is_verified) {
            return response()->json(['message' => 'Compte non activé.'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->only(['id', 'name', 'email', 'role']),
        ]);
    }

    /**
     * @group Authentification
     *
     * Profil utilisateur connecté
     *
     * Permet de récupérer les informations de l’utilisateur connecté via le token.
     *
     * @authenticated
     *
     * @response 200 {
     *  "id": 1,
     *  "name": "Alice Dupont",
     *  "email": "alice@example.com",
     *  "role": "client"
     * }
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * @group Authentification
     *
     * Déconnexion
     *
     * Supprime le token d’accès actuel.
     *
     * @authenticated
     *
     * @response 200 {
     *  "message": "Déconnexion réussie"
     * }
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnexion réussie']);
    }
}
