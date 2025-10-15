<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class PasswordResetController extends Controller
{
    // 🔹 Génère une clé unique (email + IP) pour le rate limiting
    protected function rateKey(Request $request, string $suffix = '')
    {
        $email = (string) $request->input('email', '');
        $ip = $request->ip() ?? 'unknown';
        return 'password-reset|' . sha1($email . '|' . $ip . '|' . $suffix);
    }

    // 🟢 Étape 1 : Demande de réinitialisation
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $key = $this->rateKey($request, 'forgot');
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Si l\'adresse e-mail existe, un code de réinitialisation y a été envoyé.'], 429);
        }
        RateLimiter::hit($key, 600); // 10 min

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $code = random_int(100000, 999999);
            $user->update([
                'reset_code' => Hash::make($code),
                'reset_code_expires_at' => Carbon::now()->addMinutes(15),
            ]);

            try {
                Mail::to($user->email)->send(new ResetPasswordMail($user, $code));
            } catch (\Exception $e) {
                Log::warning('Mail reset failed for ' . $user->email . ' : ' . $e->getMessage());
            }

            Log::info('Reset code generated for user_id=' . $user->id . ' email=' . $user->email);
        }

        return response()->json(['message' => 'Si l\'adresse e-mail existe, un code de réinitialisation y a été envoyé.']);
    }

    // 🟢 Étape 2 : Vérification du code
    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'reset_code' => 'required|digits:6',
        ]);

        $key = $this->rateKey($request, 'verify');
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Trop de tentatives. Réessayez plus tard.'], 429);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !$user->reset_code || !$user->reset_code_expires_at) {
            RateLimiter::hit($key, 600);
            return response()->json(['message' => 'Code invalide ou expiré.'], 400);
        }

        if (Carbon::now()->greaterThan($user->reset_code_expires_at)) {
            $user->update(['reset_code' => null, 'reset_code_expires_at' => null]);
            RateLimiter::hit($key, 600);
            return response()->json(['message' => 'Code invalide ou expiré.'], 400);
        }

        if (!Hash::check($request->reset_code, $user->reset_code)) {
            RateLimiter::hit($key, 600);
            return response()->json(['message' => 'Code invalide ou expiré.'], 400);
        }

        RateLimiter::clear($key);
        return response()->json(['message' => 'Code valide. Vous pouvez maintenant réinitialiser votre mot de passe.']);
    }

    // 🟢 Étape 3 : Réinitialisation du mot de passe
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'reset_code' => 'required|digits:6',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $key = $this->rateKey($request, 'reset');
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Trop de tentatives. Réessayez plus tard.'], 429);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !$user->reset_code || !$user->reset_code_expires_at) {
            RateLimiter::hit($key, 600);
            return response()->json(['message' => 'Code invalide ou expiré.'], 400);
        }

        if (Carbon::now()->greaterThan($user->reset_code_expires_at)) {
            $user->update(['reset_code' => null, 'reset_code_expires_at' => null]);
            RateLimiter::hit($key, 600);
            return response()->json(['message' => 'Code invalide ou expiré.'], 400);
        }

        if (!Hash::check($request->reset_code, $user->reset_code)) {
            RateLimiter::hit($key, 600);
            return response()->json(['message' => 'Code invalide ou expiré.'], 400);
        }

        // ✅ Réinitialisation
        $user->update([
            'password' => bcrypt($request->new_password),
            'reset_code' => null,
            'reset_code_expires_at' => null,
        ]);

        RateLimiter::clear($key);
        Log::info('Password reset for user_id=' . $user->id);

        return response()->json(['message' => 'Mot de passe réinitialisé avec succès.']);
    }
}
