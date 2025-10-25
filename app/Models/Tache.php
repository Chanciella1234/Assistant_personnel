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

    public function activite()
    {
        return $this->belongsTo(Activite::class, 'activite_id');
    }



    /**
 * 🔄 Mise à jour automatique du statut
 */
public function mettreAJourStatut()
{
    $now = Carbon::now();

    // 🔒 Ne rien faire si l'activité est terminée ou en pause
    if ($this->statut === 'terminee' || $this->statut === 'pause') {
        return;
    }

    // ⏳ Avant le début → "en attente"
    if ($now->lt($this->date_debut_tache)) {
        $this->statut = 'en attente';
    }

    // 🚀 Entre le début et la fin → "en cours"
    elseif ($now->between($this->date_debut_tache, $this->date_fin_tache)) {
        $this->statut = 'en cours';
    }

    // 🕒 Après la fin → on ne met PAS automatiquement "terminee"
    // l'utilisateur doit le faire manuellement
    // (donc on laisse le statut actuel)
    else {
        return;
    }

    $this->save();
}

}
