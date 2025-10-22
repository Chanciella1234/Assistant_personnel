<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activite;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StatistiquesController extends Controller
{
    /**
     * 📊 Statistiques globales des activités de l'utilisateur
     */
    public function index()
    {
        $user = Auth::user();

        $totalActivites = Activite::where('user_id', $user->id)->count();
        $totalTerminees = Activite::where('user_id', $user->id)
            ->where('statut', 'terminee')
            ->count();

        // 📈 Taux de réussite
        $taux = $totalActivites > 0 ? round(($totalTerminees / $totalActivites) * 100, 2) : 0;

        // 📊 Répartition par priorité
        $parPriorite = Activite::where('user_id', $user->id)
            ->where('statut', 'terminee')
            ->selectRaw('priorite, COUNT(*) as total')
            ->groupBy('priorite')
            ->get();

        // 📆 Répartition par période
        $now = Carbon::now();
        $jour = Activite::where('user_id', $user->id)
            ->where('statut', 'terminee')
            ->whereDate('date_fin_activite', $now->toDateString())
            ->count();

        $semaine = Activite::where('user_id', $user->id)
            ->where('statut', 'terminee')
            ->whereBetween('date_fin_activite', [$now->startOfWeek(), $now->endOfWeek()])
            ->count();

        $mois = Activite::where('user_id', $user->id)
            ->where('statut', 'terminee')
            ->whereMonth('date_fin_activite', $now->month)
            ->whereYear('date_fin_activite', $now->year)
            ->count();

        $annee = Activite::where('user_id', $user->id)
            ->where('statut', 'terminee')
            ->whereYear('date_fin_activite', $now->year)
            ->count();

        return response()->json([
            'message' => 'Statistiques des activités récupérées avec succès.',
            'data' => [
                'total_activites' => $totalActivites,
                'total_terminees' => $totalTerminees,
                'taux_reussite' => $taux . '%',
                'par_priorite' => $parPriorite,
                'par_periode' => [
                    'jour' => $jour,
                    'semaine' => $semaine,
                    'mois' => $mois,
                    'annee' => $annee,
                ],
            ],
        ]);
    }
}
