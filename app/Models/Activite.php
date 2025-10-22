<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Activite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'titre',
        'description',
        'date_debut_activite',
        'date_fin_activite',
        'priorite',
        'statut',
        'rappel_personnalise'
    ];

    protected $casts = [
        'date_debut_activite' => 'datetime',
        'date_fin_activite' => 'datetime',
    ];

    /**
     * Relation : une activité appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : une tache appartient à une activité
     */

    public function taches()
    {
        return $this->hasMany(Tache::class);
    }

     /**
     * Relation : une alerte appartient à une activité
     */
    public function alerte()
    {
        return $this->hasMany(Alerte::class);
    }



    /**
     * 🔄 Met à jour automatiquement le statut selon la date/heure actuelle
     */
    public function mettreAJourStatut()
    {
        $now = Carbon::now();

        if ($this->statut === 'terminee') return; // Ne pas toucher si terminée manuellement

        if ($now->lt($this->date_debut_activite)) {
            $this->statut = 'en attente';
        } elseif ($now->between($this->date_debut_activite, $this->date_fin_activite)) {
            if ($this->statut !== 'pause') {
                $this->statut = 'en cours';
            }
        } elseif ($now->gt($this->date_fin_activite)) {
            if ($this->statut !== 'terminee') {
                $this->statut = 'terminee';
            }
        }

        $this->save();
    }
}
