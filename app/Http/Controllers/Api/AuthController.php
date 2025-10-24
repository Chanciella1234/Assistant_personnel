<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ActivationMail;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class AuthController extends Controller
{
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
            'code_expires_at' => Carbon::now()->addMinutes(30), // ⏰ expire dans 30 min
        ]);

        Mail::to($user->email)->send(new ActivationMail($user, $code));

        return response()->json([
            'message' => 'Compte créé. Vérifiez votre e-mail pour le code d’activation.',
            'user' => $user->only(['id', 'email']),
        ], 201);
    }

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

        // Vérification de l’expiration du code
        if (Carbon::now()->greaterThan($user->code_expires_at)) {
            return response()->json(['message' => 'Le code a expiré. Veuillez demander un nouveau code.'], 400);
        }

        if ($user->code_activation != $request->code_activation) {
            return response()->json(['message' => 'Code invalide.'], 400);
        }

        $user->update([
            'is_verified' => true,
            'code_activation' => null,
            'code_expires_at' => null, // nettoyage
        ]);

        Mail::to($user->email)->send(new WelcomeMail($user));

        return response()->json(['message' => 'Compte activé avec succès. Vous pouvez maintenant vous connecter.']);
    }

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
        $user->update([
            'code_activation' => $newCode,
            'code_expires_at' => Carbon::now()->addMinutes(30), // ⏰ expire dans 30 min
        ]);

        Mail::to($user->email)->send(new ActivationMail($user, $newCode));

        return response()->json([
            'message' => 'Nouveau code envoyé à votre adresse e-mail.',
            'email' => $user->email,
        ]);
    }

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

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnexion réussie']);
    }
}
