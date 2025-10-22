<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AlerteController extends Controller
{
    /**
     * 🔍 Voir le rappel défini pour une activité
     */
    public function show($id)
    {
        $user = Auth::user();
        $activite = Activite::where('user_id', $user->id)->findOrFail($id);

        return response()->json([
            'message' => 'Rappel actuel récupéré avec succès.',
            'data' => [
                'activite_id' => $activite->id,
                'titre' => $activite->titre,
                'rappel_personnalise' => $activite->rappel_personnalise ?? 'Aucun rappel personnalisé',
                'rappel_defaut' => '10 minutes (obligatoire)',
            ]
        ]);
    }

    /**
     * 🟢 Définir ou modifier un rappel personnalisé
     */
    public function setRappel(Request $request, $id)
    {
        $validated = $request->validate([
            'rappel_personnalise' => 'required|integer|min:10|max:10080', 
            // max: 10080 = 7 jours
        ]);

        $user = Auth::user();
        $activite = Activite::where('user_id', $user->id)->findOrFail($id);

        $activite->update([
            'rappel_personnalise' => $validated['rappel_personnalise']
        ]);

        return response()->json([
            'message' => 'Rappel personnalisé mis à jour avec succès.',
            'data' => [
                'activite_id' => $activite->id,
                'rappel_personnalise' => $activite->rappel_personnalise . ' minutes',
                'rappel_defaut' => '10 minutes (obligatoire)',
            ]
        ]);
    }

    /**
     * 🗑️ Supprimer le rappel personnalisé
     */
    public function deleteRappel($id)
    {
        $user = Auth::user();
        $activite = Activite::where('user_id', $user->id)->findOrFail($id);

        $activite->update(['rappel_personnalise' => null]);

        return response()->json([
            'message' => 'Rappel personnalisé supprimé. Seule l’alerte par défaut (10 min) sera active.',
        ]);
    }
}
