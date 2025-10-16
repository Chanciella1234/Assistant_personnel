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
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Activity::where('user_id', $user->id);

        // 🔍 Recherche par mot-clé
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

        // 📅 Filtrage par période
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
            $query->orderByRaw("FIELD(priorite, 'forte', 'moyenne', 'faible')");
        } else {
            $query->orderBy('date_activite', $order)->orderBy('heure_debut', $order);
        }

        $activities = $query->get();

        // 🔄 Mettre à jour automatiquement le statut
        foreach ($activities as $activity) {
            $this->updateStatusAutomatically($activity);
        }

        return response()->json($activities);
    }

    /**
     * 🟢 Créer une activité
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
        $activity->statut = 'en attente';
        $activity->paused_at = null;
        $activity->total_pause_seconds = 0;
        $activity->save();

        $this->updateStatusAutomatically($activity);

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

        $this->updateStatusAutomatically($activity);

        return response()->json($activity);
    }

    /**
     * 🟢 Modifier une activité
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $activity = Activity::where('user_id', $user->id)->findOrFail($id);

        $request->validate([
            'titre' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'date_activite' => 'sometimes|required|date',
            'heure_debut' => 'sometimes|required',
            'heure_fin' => 'sometimes|required',
            'priorite' => 'in:faible,moyenne,forte',
            'statut' => 'in:en attente,en cours,terminee,pause',
            'rappel_personnalise' => 'nullable|integer|min:1',
        ]);

        $activity->update($request->all());
        $this->updateStatusAutomatically($activity);

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
     */
    public function pause($id)
    {
        $user = Auth::user();
        $activity = Activity::where('user_id', $user->id)->findOrFail($id);

        if ($activity->statut !== 'en cours') {
            return response()->json(['message' => 'Impossible de mettre en pause une activité non en cours.'], 400);
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
     */
    public function resume($id)
    {
        $user = Auth::user();
        $activity = Activity::where('user_id', $user->id)->findOrFail($id);

        if ($activity->statut !== 'pause') {
            return response()->json(['message' => 'Impossible de reprendre une activité qui n\'est pas en pause.'], 400);
        }

        $now = Carbon::now();
        if ($activity->paused_at) {
            $diff = $now->diffInSeconds(Carbon::parse($activity->paused_at));
            $activity->total_pause_seconds = ($activity->total_pause_seconds ?? 0) + $diff;
        }

        $activity->paused_at = null;
        $activity->statut = 'en cours';
        $activity->save();

        return response()->json([
            'message' => 'Activité reprise avec succès.',
            'data' => $activity
        ]);
    }

    /**
     * ⚙️ Met à jour automatiquement le statut selon la date/heure actuelle
     */
    private function updateStatusAutomatically($activity)
    {
        if (in_array($activity->statut, ['terminee', 'pause'])) {
            return; // ne rien changer si terminée ou en pause
        }

        $now = Carbon::now();
        $start = Carbon::parse("{$activity->date_activite} {$activity->heure_debut}");
        $end = Carbon::parse("{$activity->date_activite} {$activity->heure_fin}");

        if ($now->lt($start)) {
            $activity->statut = 'en attente';
        } elseif ($now->between($start, $end)) {
            $activity->statut = 'en cours';
        }

        $activity->save();
    }
}
