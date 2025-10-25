<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_verified',
        'code_activation',
        'langue',
        'theme',
        'reset_code',
        'reset_code_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_token',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'reset_code_expires_at' => 'datetime',
    ];

    /**
     * Relation: user -> activities (1:N)
     */
    public function activites()
    {
        return $this->hasMany(Activite::class);
    }

    /**
     * Relation: user -> tasks (1:N)
     * (si tu as les tâches directement liées à l'utilisateur; sinon tu peux
     * t'en servir via $user->activities()->with('tasks') dans les requêtes)
     */
    public function taches()
    {
        return $this->hasMany(Tache::class);
    }

    /**
     * Relation: user -> alertes (1:N)
     */
    public function alertes()
    {
        return $this->hasMany(Alerte::class);
    }

    /**
     * Relation: user -> feedbacks (1:N)
     */
    public function commentaires()
    {
        return $this->hasMany(Commentaire::class);
    }


    /**
     * Personnalise la façon dont Laravel envoie les mails à cet utilisateur.
     * (utile si tu veux envoyer vers un autre champ ou un alias)
     */
    public function routeNotificationForMail($notification = null)
    {
        return $this->email;
    }

    /**
     * Indique la locale préférée pour les notifications (Laravel 9+).
     * Laravel utilisera app()->setLocale($user->preferredLocale()) pour les notifications.
     */
    public function preferredLocale()
    {
        // Assure-toi que 'langue' contient 'fr' ou 'en' (ou une locale valide)
        return $this->langue ?? config('app.locale');
    }
}
