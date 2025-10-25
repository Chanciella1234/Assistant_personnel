<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activite;
use App\Models\Tache;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StatistiquesController extends Controller
{
    /**
     * 📊 Tableau de bord des statistiques utilisateur (Activités + Tâches)
     */
    public function index()
    {
        $user = Auth::user();
        $now = Carbon::now();

        /**
         * 1️⃣ STATISTIQUES GÉNÉRALES
         */
        $totalActivites = Activite::where('user_id', $user->id)->count();
        $totalTaches = Tache::whereHas('activite', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();

        $termineesActivites = Activite::where('user_id', $user->id)->where('statut', 'terminee')->count();
        $termineesTaches = Tache::whereHas('activite', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->where('statut', 'terminee')->count();

        // Taux de réussite global
        $tauxReussite = $totalActivites > 0
            ? round(($termineesActivites / $totalActivites) * 100, 2)
            : 0;


        /**
         * 2️⃣ RÉPARTITION PAR PRIORITÉ
         */
        $parPriorite = Activite::where('user_id', $user->id)
            ->selectRaw('priorite, COUNT(*) as total')
            ->groupBy('priorite')
            ->get();


        /**
         * 4️⃣ RÉPARTITION PAR STATUT (activités et tâches)
         */
        $parStatutActivites = Activite::where('user_id', $user->id)
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->get();

        $parStatutTaches = Tache::whereHas('activite', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->get();


        /**
         * 5️⃣ ÉVOLUTION (progression semaine / mois)
         */
        // Activités terminées par mois
        $evolutionActivites = Activite::where('user_id', $user->id)
            ->where('statut', 'terminee')
            ->select(
                DB::raw('MONTH(date_fin_activite) as mois'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('mois')
            ->get();

        // Tâches terminées par mois
        $evolutionTaches = Tache::whereHas('activite', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->where('statut', 'terminee')
            ->select(
                DB::raw('MONTH(date_fin_tache) as mois'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('mois')
            ->get();


        /**
         * 6️⃣ COMPARAISON — progression par rapport au mois précédent
         */
        $moisActuel = $now->month;
        $moisPrecedent = $now->copy()->subMonth()->month;

        $actuelles = Activite::where('user_id', $user->id)
            ->whereMonth('date_fin_activite', $moisActuel)
            ->where('statut', 'terminee')
            ->count();

        $precedentes = Activite::where('user_id', $user->id)
            ->whereMonth('date_fin_activite', $moisPrecedent)
            ->where('statut', 'terminee')
            ->count();

        $variation = $precedentes > 0
            ? round((($actuelles - $precedentes) / $precedentes) * 100, 2)
            : 100;


        /**
         * 7️⃣ TAUX DE SATISFACTION (simulé ici)
         * 👉 On suppose que le taux est basé sur le ratio d’activités terminées
         * par rapport au total (en attendant les "avis" dans le futur)
         */
        $tauxSatisfaction = $tauxReussite >= 80 ? 'Excellent' :
            ($tauxReussite >= 60 ? 'Bon' :
                ($tauxReussite >= 40 ? 'Moyen' : 'Faible'));


        /**
         * ✅ RÉPONSE JSON FINALE
         */
        return response()->json([
            'message' => 'Statistiques complètes récupérées avec succès.',
            'data' => [
                'global' => [
                    'total_activites' => $totalActivites,
                    'total_taches' => $totalTaches,
                    'activites_terminees' => $termineesActivites,
                    'taches_terminees' => $termineesTaches,
                    'taux_reussite' => $tauxReussite . '%',
                    'taux_satisfaction' => $tauxSatisfaction,
                ],
                'par_priorite' => $parPriorite,
                'par_statut' => [
                    'activites' => $parStatutActivites,
                    'taches' => $parStatutTaches,
                ],
                'evolution' => [
                    'activites' => $evolutionActivites,
                    'taches' => $evolutionTaches,
                ],
                'comparaison' => [
                    'mois_actuel' => $actuelles,
                    'mois_precedent' => $precedentes,
                    'variation_pourcentage' => $variation . '%',
                ],
            ],
        ]);
    }
}
