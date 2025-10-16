<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

        protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_verified',
        'code_activation',
        'rappel_personnalise',
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
    ];

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }


    // relations later (activites, feedbacks...)
}
