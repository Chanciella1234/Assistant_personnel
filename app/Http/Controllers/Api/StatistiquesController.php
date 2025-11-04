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
        $period = request('period', 'jour'); // Get period from query parameter

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
         * 3️⃣ RÉPARTITION PAR PÉRIODE (selon la période sélectionnée)
         */
        $parPeriode = $this->getStatsByPeriod($user->id, $period);


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
         * 5️⃣ ÉVOLUTION (progression selon la période)
         */
        $evolutionData = $this->getEvolutionData($user->id, $period);


        /**
         * 6️⃣ COMPARAISON — progression par rapport à la période précédente
         */
        $comparaison = $this->getComparisonData($user->id, $period);


        /**
         * 7️⃣ TAUX DE SATISFACTION (simulé ici)
         * 👉 On suppose que le taux est basé sur le ratio d'activités terminées
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
                'total_activites' => $totalActivites,
                'total_terminees' => $termineesActivites,
                'taux_reussite' => $tauxReussite . '%',
                'par_priorite' => $parPriorite->map(function ($item) {
                    return [
                        'priorite' => $item->priorite,
                        'total' => $item->total
                    ];
                }),
                'par_periode' => $parPeriode,
                'par_statut' => [
                    'terminees' => $parStatutActivites->where('statut', 'terminee')->first()?->total ?? 0,
                    'en_cours' => $parStatutActivites->where('statut', 'en_cours')->first()?->total ?? 0,
                    'en_retard' => $parStatutActivites->where('statut', 'en_retard')->first()?->total ?? 0,
                ],
                'evolution' => $evolutionData,
            ],
        ]);
    }

    /**
     * Get statistics by period (jour, semaine, mois, annee)
     */
    private function getStatsByPeriod($userId, $period)
    {
        $now = Carbon::now();

        switch ($period) {
            case 'jour':
                return [
                    'jour' => Activite::where('user_id', $userId)
                        ->whereDate('created_at', $now->toDateString())
                        ->count(),
                    'semaine' => 0,
                    'mois' => 0,
                    'annee' => 0
                ];

            case 'semaine':
                return [
                    'jour' => 0,
                    'semaine' => Activite::where('user_id', $userId)
                        ->whereBetween('created_at', [
                            $now->copy()->startOfWeek(),
                            $now->copy()->endOfWeek()
                        ])
                        ->count(),
                    'mois' => 0,
                    'annee' => 0
                ];

            case 'mois':
                return [
                    'jour' => 0,
                    'semaine' => 0,
                    'mois' => Activite::where('user_id', $userId)
                        ->whereYear('created_at', $now->year)
                        ->whereMonth('created_at', $now->month)
                        ->count(),
                    'annee' => 0
                ];

            case 'annee':
                return [
                    'jour' => 0,
                    'semaine' => 0,
                    'mois' => 0,
                    'annee' => Activite::where('user_id', $userId)
                        ->whereYear('created_at', $now->year)
                        ->count()
                ];

            default:
                return [
                    'jour' => 0,
                    'semaine' => 0,
                    'mois' => 0,
                    'annee' => 0
                ];
        }
    }

    /**
     * Get evolution data based on period
     */
    private function getEvolutionData($userId, $period)
    {
        $now = Carbon::now();

        switch ($period) {
            case 'jour':
                // Last 7 days evolution
                $evolution = [];
                for ($i = 6; $i >= 0; $i--) {
                    $date = $now->copy()->subDays($i);
                    $count = Activite::where('user_id', $userId)
                        ->whereDate('created_at', $date->toDateString())
                        ->count();
                    $completed = Activite::where('user_id', $userId)
                        ->where('statut', 'terminee')
                        ->whereDate('date_fin_activite', $date->toDateString())
                        ->count();
                    $evolution[] = [
                        'date' => $date->format('Y-m-d'),
                        'ajoutees' => $count,
                        'terminees' => $completed
                    ];
                }
                return $evolution;

            case 'semaine':
                // Last 4 weeks evolution
                $evolution = [];
                for ($i = 3; $i >= 0; $i--) {
                    $weekStart = $now->copy()->subWeeks($i)->startOfWeek();
                    $weekEnd = $now->copy()->subWeeks($i)->endOfWeek();
                    $count = Activite::where('user_id', $userId)
                        ->whereBetween('created_at', [$weekStart, $weekEnd])
                        ->count();
                    $completed = Activite::where('user_id', $userId)
                        ->where('statut', 'terminee')
                        ->whereBetween('date_fin_activite', [$weekStart, $weekEnd])
                        ->count();
                    $evolution[] = [
                        'date' => $weekStart->format('Y-m-d'),
                        'ajoutees' => $count,
                        'terminees' => $completed
                    ];
                }
                return $evolution;

            case 'mois':
                // Last 6 months evolution
                $evolution = [];
                for ($i = 5; $i >= 0; $i--) {
                    $monthStart = $now->copy()->subMonths($i)->startOfMonth();
                    $monthEnd = $now->copy()->subMonths($i)->endOfMonth();
                    $count = Activite::where('user_id', $userId)
                        ->whereBetween('created_at', [$monthStart, $monthEnd])
                        ->count();
                    $completed = Activite::where('user_id', $userId)
                        ->where('statut', 'terminee')
                        ->whereBetween('date_fin_activite', [$monthStart, $monthEnd])
                        ->count();
                    $evolution[] = [
                        'date' => $monthStart->format('Y-m-d'),
                        'ajoutees' => $count,
                        'terminees' => $completed
                    ];
                }
                return $evolution;

            case 'annee':
                // Last 3 years evolution
                $evolution = [];
                for ($i = 2; $i >= 0; $i--) {
                    $yearStart = $now->copy()->subYears($i)->startOfYear();
                    $yearEnd = $now->copy()->subYears($i)->endOfYear();
                    $count = Activite::where('user_id', $userId)
                        ->whereBetween('created_at', [$yearStart, $yearEnd])
                        ->count();
                    $completed = Activite::where('user_id', $userId)
                        ->where('statut', 'terminee')
                        ->whereBetween('date_fin_activite', [$yearStart, $yearEnd])
                        ->count();
                    $evolution[] = [
                        'date' => $yearStart->format('Y-m-d'),
                        'ajoutees' => $count,
                        'terminees' => $completed
                    ];
                }
                return $evolution;

            default:
                return [];
        }
    }

    /**
     * Get comparison data based on period
     */
    private function getComparisonData($userId, $period)
    {
        $now = Carbon::now();

        switch ($period) {
            case 'jour':
                $current = Activite::where('user_id', $userId)
                    ->whereDate('created_at', $now->toDateString())
                    ->count();
                $previous = Activite::where('user_id', $userId)
                    ->whereDate('created_at', $now->copy()->subDay()->toDateString())
                    ->count();
                break;

            case 'semaine':
                $current = Activite::where('user_id', $userId)
                    ->whereBetween('created_at', [
                        $now->copy()->startOfWeek(),
                        $now->copy()->endOfWeek()
                    ])
                    ->count();
                $previous = Activite::where('user_id', $userId)
                    ->whereBetween('created_at', [
                        $now->copy()->subWeek()->startOfWeek(),
                        $now->copy()->subWeek()->endOfWeek()
                    ])
                    ->count();
                break;

            case 'mois':
                $current = Activite::where('user_id', $userId)
                    ->whereYear('created_at', $now->year)
                    ->whereMonth('created_at', $now->month)
                    ->count();
                $previous = Activite::where('user_id', $userId)
                    ->whereYear('created_at', $now->copy()->subMonth()->year)
                    ->whereMonth('created_at', $now->copy()->subMonth()->month)
                    ->count();
                break;

            case 'annee':
                $current = Activite::where('user_id', $userId)
                    ->whereYear('created_at', $now->year)
                    ->count();
                $previous = Activite::where('user_id', $userId)
                    ->whereYear('created_at', $now->copy()->subYear()->year)
                    ->count();
                break;

            default:
                $current = 0;
                $previous = 0;
        }

        $variation = $previous > 0
            ? round((($current - $previous) / $previous) * 100, 2)
            : ($current > 0 ? 100 : 0);

        return [
            'periode_actuelle' => $current,
            'periode_precedente' => $previous,
            'variation_pourcentage' => $variation . '%'
        ];
    }

    /**
     * 📊 Statistiques détaillées des statuts pour activités et tâches
     */
    public function statutsDetailles()
    {
        $user = Auth::user();

        // Statistiques des activités
        $activitesStats = Activite::where('user_id', $user->id)
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->toArray();

        $activites = [
            'terminees' => $activitesStats['terminee'] ?? 0,
            'en_cours' => $activitesStats['en cours'] ?? 0,
            'en_attente' => $activitesStats['en attente'] ?? 0,
            'en_retard' => $activitesStats['en_retard'] ?? 0,
        ];

        // Statistiques des tâches
        $tachesStats = Tache::whereHas('activite', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->toArray();

        $taches = [
            'terminees' => $tachesStats['terminee'] ?? 0,
            'en_cours' => $tachesStats['en cours'] ?? 0,
            'en_attente' => $tachesStats['en attente'] ?? 0,
            'en_retard' => $tachesStats['en_retard'] ?? 0,
        ];

        return response()->json([
            'message' => 'Statistiques détaillées des statuts récupérées avec succès.',
            'data' => [
                'activites' => $activites,
                'taches' => $taches,
            ],
        ]);
    }
}
