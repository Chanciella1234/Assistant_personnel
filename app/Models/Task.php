<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'titre',
        'description',
        'date_debut',
        'date_fin',
        'statut',
    ];

    /**
     * Relation avec l’activité parente
     */
    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}
