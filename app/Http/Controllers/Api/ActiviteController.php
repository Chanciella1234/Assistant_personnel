<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ActiviteController extends Controller
{
    /**
     * 🟢 Lister les activités (avec recherche + filtrage)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Activite::where('user_id', $user->id);

        // 🔎 Recherche par mot-clé
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('titre', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // 🗓️ Filtrage par période
        if ($request->filled('periode')) {
            $now = Carbon::now();

            switch ($request->periode) {
                case 'jour':
                    $query->whereDate('date_debut_activite', $now->toDateString());
                    break;
                case 'semaine':
                    $query->whereBetween('date_debut_activite', [$now->startOfWeek(), $now->endOfWeek()]);
                    break;
                case 'mois':
                    $query->whereMonth('date_debut_activite', $now->month)
                          ->whereYear('date_debut_activite', $now->year);
                    break;
                case 'annee':
                    $query->whereYear('date_debut_activite', $now->year);
                    break;
            }
        }

        // ⚙️ Tri dynamique
        if ($request->filled('sort')) {
            if ($request->sort === 'date') {
                $query->orderBy('date_debut_activite', 'asc');
            } elseif ($request->sort === 'priorite') {
                $query->orderByRaw("FIELD(priorite, 'forte', 'moyenne', 'faible')");
            }
        } else {
            $query->latest();
        }

        // 🕒 Mettre à jour automatiquement les statuts avant d'afficher
        $activites = $query->get();
        foreach ($activites as $a) {
            $a->mettreAJourStatut();
        }

        return response()->json([
            'message' => 'Liste des activités récupérée avec succès.',
            'data' => $activites
        ]);
    }

    /**
     * 🟢 Créer une activité
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date_debut_activite' => 'required|date|after:now',
            'date_fin_activite' => 'required|date|after:date_debut_activite',
            'priorite' => 'required|in:forte,moyenne,faible',
            'rappel_personnalise' => 'nullable|integer|min:10|max:10080' // en minutes (jusqu’à 7 jours)
        ]);

        $activite = Activite::create([
            'user_id' => Auth::id(),
            'titre' => $validated['titre'],
            'description' => $validated['description'] ?? null,
            'date_debut_activite' => $validated['date_debut_activite'],
            'date_fin_activite' => $validated['date_fin_activite'],
            'priorite' => $validated['priorite'],
            'statut' => 'en attente',
            'rappel_personnalise' => $validated['rappel_personnalise'] ?? null,
        ]);

        return response()->json([
            'message' => 'Activité créée avec succès.',
            'data' => [
                'id' => $activite->id,
                'titre' => $activite->titre,
                'date_debut_activite' => $activite->date_debut_activite,
                'rappel_personnalise' => $activite->rappel_personnalise
                    ? "{$activite->rappel_personnalise} minutes"
                    : "Aucun (10 minutes par défaut)",
            ]
        ], 201);
    }


    /**
     * 🟢 Afficher une activité
     */
    public function show($id)
    {
        $user = Auth::user();
        $activite = Activite::where('user_id', $user->id)->findOrFail($id);

        $activite->mettreAJourStatut();

        return response()->json([
            'message' => 'Activité récupérée avec succès.',
            'data' => $activite,
        ]);
    }

    /**
     * 🟢 Mettre à jour une activité
     */
    public function update(Request $request, $id)
    {
        $activite = Activite::findOrFail($id);

        $request->validate([
            'titre' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'date_debut_activite' => 'sometimes|date',
            'date_fin_activite' => 'sometimes|date|after:date_debut_activite',
            'priorite' => 'sometimes|in:forte,moyenne,faible',
            'statut' => 'sometimes|in:en attente,en cours,pause,terminee',
        ]);

        // 🔹 Si l'utilisateur veut marquer comme terminée :
        if ($request->filled('statut') && $request->statut === 'terminee') {
            // l'utilisateur marque manuellement la fin
            $activite->statut = 'terminee';
        } else {
            // sinon on garde le comportement normal (mise à jour auto possible)
            $activite->fill($request->only([
                'titre',
                'description',
                'date_debut_activite',
                'date_fin_activite',
                'priorite',
                'statut',
            ]));
        }

        $activite->save();

        return response()->json([
            'message' => 'Activité mise à jour avec succès.',
            'data' => $activite
        ]);
    }



    /**
     * 🟢 Supprimer une activité
     */
    public function destroy($id)
    {
        $activite = Activite::where('user_id', Auth::id())->findOrFail($id);
        $activite->delete();

        return response()->json([
            'message' => 'Activité supprimée avec succès.',
        ]);
    }

        /**
     * 🟡 Mettre une activité en pause
     */
    public function pause($id)
    {
        $activite = Activite::where('user_id', Auth::id())->findOrFail($id);

        // Vérifie si elle est en cours
        if ($activite->statut !== 'en cours') {
            return response()->json([
                'message' => "Impossible de mettre en pause une activité qui n'est pas en cours.",
            ], 400);
        }

        $activite->update(['statut' => 'pause']);

        return response()->json([
            'message' => 'Activité mise en pause avec succès.',
            'data' => $activite,
        ]);
    }

    /**
     * 🟢 Reprendre une activité mise en pause
     */
    public function reprendre($id)
    {
        $activite = Activite::where('user_id', Auth::id())->findOrFail($id);

        if ($activite->statut !== 'pause') {
            return response()->json([
                'message' => "Cette activité n'est pas en pause.",
            ], 400);
        }

        $now = now();

        if ($now->gt($activite->date_fin_activite)) {
            return response()->json([
                'message' => "Impossible de reprendre une activité dont la période est déjà terminée.",
            ], 400);
        }

        // Si l'heure est encore valide, on peut la reprendre
        $activite->update(['statut' => 'en cours']);

        return response()->json([
            'message' => 'Activité reprise avec succès.',
            'data' => $activite,
        ]);
    }

}
