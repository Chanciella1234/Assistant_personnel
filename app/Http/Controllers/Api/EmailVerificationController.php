<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return response()->json(['message' => 'Token manquant'], 400);
        }

        $user = User::where('verification_token', $token)->first();

        if (!$user) {
            return response()->json(['message' => 'Token invalide ou expiré'], 404);
        }

        $user->update([
            'is_verified' => true,
            'verification_token' => null
        ]);

        return response()->json(['message' => 'E-mail vérifié avec succès. Vous pouvez maintenant vous connecter.']);
    }
}
