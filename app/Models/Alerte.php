<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alerte extends Model
{
    use HasFactory;

    protected $fillable = [
        'activite_id',
        'user_id',
        'type',
        'delai_minutes',
        'envoyee',
        'message',
    ];

    public function activite()
    {
        return $this->belongsTo(Activite::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
