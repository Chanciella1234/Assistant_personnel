<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Commentaire extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'contenu',
    ];

    /**
     * 🔗 Relation : un commentaire appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 🕒 Formate la date de création (pour l'affichage dans l'API)
     */
    protected $appends = ['date_commentaire'];

    public function getDateCommentaireAttribute()
    {
        return Carbon::parse($this->created_at)
            ->locale('fr')
            ->translatedFormat('d F Y à H:i');
    }
}
