<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Tache extends Model
{
    use HasFactory;

    protected $fillable = [
        'activite_id',
        'titre',
        'date_debut_tache',
        'date_fin_tache',
        'statut',
    ];

    protected $casts = [
        'date_debut_tache' => 'datetime',
        'date_fin_tache' => 'datetime',
    ];

    /**
     * 🔗 Relation : une activité appartient à une tâche
     */
    public function taches()
    {
        return $this->hasMany(Tache::class);
    }


    /**
     * 🔄 Mise à jour automatique du statut
     */
    public function mettreAJourStatut()
    {
        $now = Carbon::now();

        if ($this->statut === 'terminee') return;
        if ($this->statut === 'pause') return;

        if ($now->lt($this->date_debut_tache)) {
            $this->statut = 'en attente';
        } elseif ($now->between($this->date_debut_tache, $this->date_fin_tache)) {
            $this->statut = 'en cours';
        } elseif ($now->gt($this->date_fin_tache)) {
            $this->statut = 'terminee';
        }

        $this->save();
    }
}
