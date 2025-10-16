<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'titre',
        'description',
        'date_activite',
        'heure_debut',
        'heure_fin',
        'priorite',
        'statut',
        'rappel_personnalise',
        'paused_at',
        'resumed_at',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

     /**
     * Relation avec les taches
     */

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }


    /**
     * Mettre à jour automatiquement le statut selon la date/heure
     */
    public function updateStatusAutomatically()
    {
        $now = Carbon::now();
        $debut = Carbon::parse($this->date_activite . ' ' . $this->heure_debut);
        $fin = Carbon::parse($this->date_activite . ' ' . $this->heure_fin);

        if ($this->statut !== 'terminee' && $this->statut !== 'pause') {
            if ($now->lt($debut)) {
                $this->statut = 'en attente';
            } elseif ($now->between($debut, $fin)) {
                $this->statut = 'en cours';
            } elseif ($now->gt($fin)) {
                $this->statut = 'en cours'; // reste "en cours" tant que l’utilisateur ne coche pas "terminée"
            }
            $this->save();
        }
    }
}
