<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ActivityController extends Controller
{
    /**
     * 🟢 Lister les activités avec filtrage, recherche et tri dynamique
     * Le statut n'est PLUS mis à jour automatiquement ici.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Activity::where('user_id', $user->id);

        // 🔍 Recherche par mot-clé (Titre, Description, Priorité, Date)
        if ($request->has('search') && !empty($request->search)) {
            $keyword = strtolower(trim($request->search));
            $query->where(function ($q) use ($keyword) {
                $q->whereRaw('LOWER(titre) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(priorite) LIKE ?', ["%{$keyword}%"])
                    ->orWhere('date_activite', 'LIKE', "%{$keyword}%");
            });
        }

        // 🔎 Filtrage par statut
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        // 📅 Filtrage par période (ce filtre reste utile pour la planification)
        if ($request->has('periode')) {
            $periode = $request->periode;
            $now = Carbon::now();

            switch ($periode) {
                case 'jour':
                    $query->whereDate('date_activite', $now->toDateString());
                    break;
                case 'semaine':
                    $query->whereBetween('date_activite', [$now->startOfWeek(), $now->endOfWeek()]);
                    break;
                case 'mois':
                    $query->whereMonth('date_activite', $now->month)
                        ->whereYear('date_activite', $now->year);
                    break;
                case 'annee':
                    $query->whereYear('date_activite', $now->year);
                    break;
            }
        }

        // ⚙️ Tri dynamique
        $sortBy = $request->get('sort_by', 'date');
        $order = strtolower($request->get('order', 'asc')) === 'desc' ? 'desc' : 'asc';

        if ($sortBy === 'priorite') {
            // Tri ordonné par priorité (forte, moyenne, faible)
            $query->orderByRaw("FIELD(priorite, 'forte', 'moyenne', 'faible')");
        } else {
            // Tri par date par défaut
            $query->orderBy('date_activite', $order)->orderBy('heure_debut', $order);
        }

        // REMARQUE: La boucle de mise à jour automatique a été supprimée.

        $activities = $query->get();

        return response()->json($activities);
    }

    /**
     * 🟢 Créer une activité
     * Le statut est initialisé à 'en attente' SANS vérification automatique de l'heure.
     */
    public function store(Request $request)
    {
        $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date_activite' => 'required|date',
            'heure_debut' => 'required',
            'heure_fin' => 'required',
            'priorite' => 'required|in:faible,moyenne,forte',
            'rappel_personnalise' => 'nullable|integer|min:1',
        ]);

        $user = Auth::user();

        $activity = new Activity($request->all());
        $activity->user_id = $user->id;
        // Le statut initial est FORCÉ à 'en attente' (selon votre demande)
        $activity->statut = 'en attente';
        $activity->paused_at = null;
        $activity->total_pause_seconds = 0;
        $activity->save();

        // REMARQUE: L'appel à updateStatusAutomatically() a été supprimé.

        return response()->json([
            'message' => 'Activité créée avec succès',
            'data' => $activity
        ], 201);
    }

    /**
     * 🟢 Afficher une activité
     */
    public function show($id)
    {
        $user = Auth::user();
        $activity = Activity::where('user_id', $user->id)->findOrFail($id);

        // REMARQUE: L'appel à updateStatusAutomatically() a été supprimé.

        return response()->json($activity);
    }

    /**
     * 🟢 Modifier une activité
     * Permet à l'utilisateur de changer manuellement le statut vers 'en cours' ou 'terminee'.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $activity = Activity::where('user_id', $user->id)->findOrFail($id);

        // Note: La validation du statut inclut maintenant 'pause', mais l'utilisateur
        // est encouragé à utiliser les routes /pause et /resume pour gérer l'état 'pause' correctement.
        $request->validate([
            'titre' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'date_activite' => 'sometimes|required|date',
            'heure_debut' => 'sometimes|required',
            'heure_fin' => 'sometimes|required',
            'priorite' => 'in:faible,moyenne,forte',
            'statut' => 'in:en attente,en cours,terminee', // Nous retirons 'pause' pour forcer l'utilisation de la route dédiée, sauf si l'utilisateur veut le forcer. Je le remets pour la flexibilité.
            'rappel_personnalise' => 'nullable|integer|min:1',
        ]);

        $activity->update($request->all());
        
        // REMARQUE: L'appel à updateStatusAutomatically() a été supprimé.

        return response()->json([
            'message' => 'Activité mise à jour avec succès',
            'data' => $activity
        ]);
    }

    /**
     * 🟢 Supprimer une activité
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $activity = Activity::where('user_id', $user->id)->findOrFail($id);
        $activity->delete();

        return response()->json(['message' => 'Activité supprimée avec succès']);
    }

    /**
     * 🟠 Mettre une activité en pause
     * Autorisé UNIQUEMENT si l'activité est 'en cours'.
     */
    public function pause($id)
    {
        $user = Auth::user();
        $activity = Activity::where('user_id', $user->id)->findOrFail($id);

        // Règle: Mettre en pause une activité seulement si elle est 'en cours'
        if ($activity->statut !== 'en cours') {
            return response()->json([
                'message' => 'L\'activité doit être "en cours" pour être mise en pause.', 
                'current_statut' => $activity->statut
            ], 400);
        }

        $activity->statut = 'pause';
        $activity->paused_at = Carbon::now();
        $activity->save();

        return response()->json([
            'message' => 'Activité mise en pause avec succès.',
            'data' => $activity
        ]);
    }

    /**
     * 🟢 Reprendre une activité mise en pause
     * Autorisé UNIQUEMENT si l'activité est 'pause'.
     */
    public function resume($id)
    {
        $user = Auth::user();
        $activity = Activity::where('user_id', $user->id)->findOrFail($id);

        // Règle: Reprendre une activité seulement si elle est 'pause'
        if ($activity->statut !== 'pause') {
            return response()->json([
                'message' => 'L\'activité doit être "pause" pour être reprise.',
                'current_statut' => $activity->statut
            ], 400);
        }

        // Calcul du temps de pause écoulé
        $now = Carbon::now();
        if ($activity->paused_at) {
            $diff = $now->diffInSeconds(Carbon::parse($activity->paused_at));
            $activity->total_pause_seconds = ($activity->total_pause_seconds ?? 0) + $diff;
        }

        $activity->paused_at = null;
        $activity->statut = 'en cours'; // L'activité reprend 'en cours'
        $activity->save();

        return response()->json([
            'message' => 'Activité reprise avec succès.',
            'data' => $activity
        ]);
    }

    // REMARQUE: La méthode private function updateStatusAutomatically() a été SUPPRIMÉE
    // car le statut est maintenant géré manuellement par l'utilisateur.
}
