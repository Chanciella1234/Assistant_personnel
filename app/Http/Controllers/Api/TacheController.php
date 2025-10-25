<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tache;
use App\Models\Activite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TacheController extends Controller
{
    /**
     * 📋 Lister les tâches d'une activité
     */
    public function index($activite_id)
    {
        $activite = Activite::where('user_id', Auth::id())->findOrFail($activite_id);

        $taches = $activite->taches;
        foreach ($taches as $t) {
            $t->mettreAJourStatut();
        }

        return response()->json([
            'message' => "Liste des tâches de l'activité récupérée avec succès.",
            'data' => $taches,
        ]);
    }

    /**
     * 🟢 Créer une tâche pour une activité donnée
     */
    public function store(Request $request, $activite_id)
    {
        $activite = Activite::where('user_id', Auth::id())->findOrFail($activite_id);

        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'date_debut_tache' => 'required|date',
            'date_fin_tache' => 'required|date|after:date_debut_tache',
        ]);

        $validated['activite_id'] = $activite->id;
        $validated['statut'] = 'en attente';

        $tache = Tache::create($validated);

        return response()->json([
            'message' => 'Tâche créée avec succès.',
            'data' => $tache,
        ], 201);
    }

    /**
     * 🟡 Mettre à jour une tâche
     */
    public function update(Request $request, $activite_id, $tache_id)
    {
        $user = Auth::user();

        // Vérifie que la tâche appartient bien à l'utilisateur
        $tache = Tache::where('id', $tache_id)
            ->whereHas('activite', function ($q) use ($user, $activite_id) {
                $q->where('user_id', $user->id)
                  ->where('id', $activite_id);
            })->firstOrFail();

        $request->validate([
            'titre' => 'sometimes|string|max:255',
            'date_debut_tache' => 'sometimes|date',
            'date_fin_tache' => 'sometimes|date|after:date_debut_tache',
            'statut' => 'sometimes|in:en attente,en cours,pause,terminee',
        ]);

        // 🟣 Si l'utilisateur veut marquer la tâche comme terminée manuellement
        if ($request->filled('statut') && $request->statut === 'terminee') {
            $tache->statut = 'terminee';
        } 
        // 🟡 Sinon on met à jour les autres champs
        else {
            $tache->fill($request->only([
                'titre',
                'date_debut_tache',
                'date_fin_tache',
                'statut',
            ]));
        }

        $tache->save();

        return response()->json([
            'message' => 'Tâche mise à jour avec succès.',
            'data' => $tache
        ]);
    }
}


    /**
     * 🗑️ Supprimer une tâche
     */
    public function destroy($activite_id, $tache_id)
    {
        $activite = Activite::where('user_id', Auth::id())->findOrFail($activite_id);
        $tache = $activite->taches()->findOrFail($tache_id);

        $tache->delete();

        return response()->json([
            'message' => 'Tâche supprimée avec succès.',
        ]);
    }

    /**
     * 🟡 Mettre une tâche en pause
     */
    public function pause($activite_id, $tache_id)
    {
        $tache = Tache::whereHas('activite', fn($q) => $q->where('user_id', Auth::id()))
                      ->findOrFail($tache_id);

        if ($tache->statut !== 'en cours') {
            return response()->json(['message' => "Impossible de mettre en pause une tâche qui n'est pas en cours."], 400);
        }

        $tache->update(['statut' => 'pause']);

        return response()->json(['message' => 'Tâche mise en pause avec succès.', 'data' => $tache]);
    }

    /**
     * 🟢 Reprendre une tâche mise en pause
     */
    public function reprendre($activite_id, $tache_id)
    {
        $tache = Tache::whereHas('activite', fn($q) => $q->where('user_id', Auth::id()))
                      ->findOrFail($tache_id);

        if ($tache->statut !== 'pause') {
            return response()->json(['message' => "Cette tâche n'est pas en pause."], 400);
        }

        $now = now();
        if ($now->gt($tache->date_fin_tache)) {
            return response()->json(['message' => "Impossible de reprendre une tâche déjà terminée."], 400);
        }

        $tache->update(['statut' => 'en cours']);

        return response()->json(['message' => 'Tâche reprise avec succès.', 'data' => $tache]);
    }
}
