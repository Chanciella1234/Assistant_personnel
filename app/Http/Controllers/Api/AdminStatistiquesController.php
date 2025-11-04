<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Activite;
use App\Models\Tache;
use App\Models\Commentaire;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AdminStatistiquesController extends Controller
{
    /**
     * 📊 Affiche les statistiques globales (pour l'administrateur)
     */
    public function index()
    {
        $admin = Auth::user();

        // Vérifie que c’est bien un admin
        if ($admin->role !== 'admin') {
            return response()->json([
                'message' => 'Accès refusé. Réservé à l’administrateur.'
            ], 403);
        }

        // 🔹 Statistiques utilisateurs
        $totalUtilisateurs = User::where('role', '!=', 'admin')->count();

        // 🔹 Statistiques activités
        $totalActivites = Activite::count();
        $activitesParStatut = Activite::selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->get();
        $activitesParPriorite = Activite::selectRaw('priorite, COUNT(*) as total')
            ->groupBy('priorite')
            ->get();

        // 🔹 Statistiques tâches
        $totalTaches = Tache::count();
        $tachesParStatut = Tache::selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->get();

        // 🔹 Statistiques commentaires
        $totalCommentaires = Commentaire::count();

        // 🔹 Statistiques temporelles
        $moisCourant = Carbon::now()->month;
        $anneeCourante = Carbon::now()->year;
        $activitesCeMois = Activite::whereMonth('created_at', $moisCourant)
            ->whereYear('created_at', $anneeCourante)
            ->count();
        $utilisateursCeMois = User::whereMonth('created_at', $moisCourant)
            ->whereYear('created_at', $anneeCourante)
            ->count();

        return response()->json([
            'message' => 'Statistiques globales récupérées avec succès.',
            'data' => [
                'utilisateurs' => [
                    'total' => $totalUtilisateurs,
                    'nouveaux_ce_mois' => $utilisateursCeMois,
                ],
                'activites' => [
                    'total' => $totalActivites,
                    'par_statut' => $activitesParStatut,
                    'par_priorite' => $activitesParPriorite,
                    'cree_ce_mois' => $activitesCeMois,
                ],
                'taches' => [
                    'total' => $totalTaches,
                    'par_statut' => $tachesParStatut,
                ],
                'commentaires' => [
                    'total' => $totalCommentaires,
                ],
            ]
        ]);
    }

    /**
     * 📈 Progression d'un utilisateur spécifique dans le temps
     */
    public function userProgression($userId)
    {
        $admin = Auth::user();

        // Vérifie que c’est bien un admin
        if ($admin->role !== 'admin') {
            return response()->json([
                'message' => 'Accès refusé. Réservé à l’administrateur.'
            ], 403);
        }

        $user = User::findOrFail($userId);

        $period = request('period', 'mois'); // jour, semaine, mois
        $now = Carbon::now();

        $evolution = [];

        switch ($period) {
            case 'jour':
                // Derniers 7 jours
                for ($i = 6; $i >= 0; $i--) {
                    $date = $now->copy()->subDays($i);
                    $activitesCreees = Activite::where('user_id', $userId)
                        ->whereDate('created_at', $date->toDateString())
                        ->count();
                    $activitesTerminees = Activite::where('user_id', $userId)
                        ->where('statut', 'terminee')
                        ->whereDate('updated_at', $date->toDateString())
                        ->count();
                    $tachesCreees = Tache::whereHas('activite', fn($q) => $q->where('user_id', $userId))
                        ->whereDate('created_at', $date->toDateString())
                        ->count();
                    $tachesTerminees = Tache::whereHas('activite', fn($q) => $q->where('user_id', $userId))
                        ->where('statut', 'terminee')
                        ->whereDate('updated_at', $date->toDateString())
                        ->count();
                    $evolution[] = [
                        'date' => $date->format('Y-m-d'),
                        'activites_creees' => $activitesCreees,
                        'activites_terminees' => $activitesTerminees,
                        'taches_creees' => $tachesCreees,
                        'taches_terminees' => $tachesTerminees,
                    ];
                }
                break;

            case 'semaine':
                // Dernières 4 semaines
                for ($i = 3; $i >= 0; $i--) {
                    $weekStart = $now->copy()->subWeeks($i)->startOfWeek();
                    $weekEnd = $now->copy()->subWeeks($i)->endOfWeek();
                    $activitesCreees = Activite::where('user_id', $userId)
                        ->whereBetween('created_at', [$weekStart, $weekEnd])
                        ->count();
                    $activitesTerminees = Activite::where('user_id', $userId)
                        ->where('statut', 'terminee')
                        ->whereBetween('updated_at', [$weekStart, $weekEnd])
                        ->count();
                    $tachesCreees = Tache::whereHas('activite', fn($q) => $q->where('user_id', $userId))
                        ->whereBetween('created_at', [$weekStart, $weekEnd])
                        ->count();
                    $tachesTerminees = Tache::whereHas('activite', fn($q) => $q->where('user_id', $userId))
                        ->where('statut', 'terminee')
                        ->whereBetween('updated_at', [$weekStart, $weekEnd])
                        ->count();
                    $evolution[] = [
                        'periode' => $weekStart->format('Y-m-d') . ' à ' . $weekEnd->format('Y-m-d'),
                        'activites_creees' => $activitesCreees,
                        'activites_terminees' => $activitesTerminees,
                        'taches_creees' => $tachesCreees,
                        'taches_terminees' => $tachesTerminees,
                    ];
                }
                break;

            case 'mois':
            default:
                // Derniers 6 mois
                for ($i = 5; $i >= 0; $i--) {
                    $monthStart = $now->copy()->subMonths($i)->startOfMonth();
                    $monthEnd = $now->copy()->subMonths($i)->endOfMonth();
                    $activitesCreees = Activite::where('user_id', $userId)
                        ->whereBetween('created_at', [$monthStart, $monthEnd])
                        ->count();
                    $activitesTerminees = Activite::where('user_id', $userId)
                        ->where('statut', 'terminee')
                        ->whereBetween('updated_at', [$monthStart, $monthEnd])
                        ->count();
                    $tachesCreees = Tache::whereHas('activite', fn($q) => $q->where('user_id', $userId))
                        ->whereBetween('created_at', [$monthStart, $monthEnd])
                        ->count();
                    $tachesTerminees = Tache::whereHas('activite', fn($q) => $q->where('user_id', $userId))
                        ->where('statut', 'terminee')
                        ->whereBetween('updated_at', [$monthStart, $monthEnd])
                        ->count();
                    $evolution[] = [
                        'periode' => $monthStart->format('Y-m'),
                        'activites_creees' => $activitesCreees,
                        'activites_terminees' => $activitesTerminees,
                        'taches_creees' => $tachesCreees,
                        'taches_terminees' => $tachesTerminees,
                    ];
                }
                break;
        }

        return response()->json([
            'message' => "Progression de l'utilisateur {$user->name} récupérée avec succès.",
            'data' => [
                'user_id' => $userId,
                'user_name' => $user->name,
                'periode' => $period,
                'evolution' => $evolution,
            ]
        ]);
    }

    /**
     * 📊 Évolution des utilisateurs (nouveaux et actifs) pour graphiques
     */
    public function usersEvolution()
    {
        $admin = Auth::user();

        // Vérifie que c’est bien un admin
        if ($admin->role !== 'admin') {
            return response()->json([
                'message' => 'Accès refusé. Réservé à l’administrateur.'
            ], 403);
        }

        $period = request('period', 'mois'); // jour, semaine, mois
        $now = Carbon::now();

        $evolution = [];
        $previousNouveaux = 0;
        $previousActifs = 0;

        switch ($period) {
            case 'jour':
                // Derniers 7 jours
                for ($i = 6; $i >= 0; $i--) {
                    $date = $now->copy()->subDays($i);
                    $nouveauxUtilisateurs = User::where('role', '!=', 'admin')
                        ->whereDate('created_at', $date->toDateString())
                        ->count();
                    $utilisateursActifs = User::where('role', '!=', 'admin')
                        ->whereDate('updated_at', $date->toDateString())
                        ->count();
                    $variationNouveaux = $previousNouveaux > 0 ? (($nouveauxUtilisateurs - $previousNouveaux) / $previousNouveaux) * 100 : 0;
                    $variationActifs = $previousActifs > 0 ? (($utilisateursActifs - $previousActifs) / $previousActifs) * 100 : 0;
                    $evolution[] = [
                        'periode' => $date->format('Y-m-d'),
                        'nouveaux_utilisateurs' => $nouveauxUtilisateurs,
                        'utilisateurs_actifs' => $utilisateursActifs,
                        'variation_nouveaux' => round($variationNouveaux, 2) . '%',
                        'variation_actifs' => round($variationActifs, 2) . '%',
                    ];
                    $previousNouveaux = $nouveauxUtilisateurs;
                    $previousActifs = $utilisateursActifs;
                }
                break;

            case 'semaine':
                // Dernières 4 semaines
                for ($i = 3; $i >= 0; $i--) {
                    $weekStart = $now->copy()->subWeeks($i)->startOfWeek();
                    $weekEnd = $now->copy()->subWeeks($i)->endOfWeek();
                    $nouveauxUtilisateurs = User::where('role', '!=', 'admin')
                        ->whereBetween('created_at', [$weekStart, $weekEnd])
                        ->count();
                    $utilisateursActifs = User::where('role', '!=', 'admin')
                        ->whereBetween('updated_at', [$weekStart, $weekEnd])
                        ->count();
                    $variationNouveaux = $previousNouveaux > 0 ? (($nouveauxUtilisateurs - $previousNouveaux) / $previousNouveaux) * 100 : 0;
                    $variationActifs = $previousActifs > 0 ? (($utilisateursActifs - $previousActifs) / $previousActifs) * 100 : 0;
                    $evolution[] = [
                        'periode' => $weekStart->format('Y-m-d') . ' à ' . $weekEnd->format('Y-m-d'),
                        'nouveaux_utilisateurs' => $nouveauxUtilisateurs,
                        'utilisateurs_actifs' => $utilisateursActifs,
                        'variation_nouveaux' => round($variationNouveaux, 2) . '%',
                        'variation_actifs' => round($variationActifs, 2) . '%',
                    ];
                    $previousNouveaux = $nouveauxUtilisateurs;
                    $previousActifs = $utilisateursActifs;
                }
                break;

            case 'mois':
            default:
                // Derniers 6 mois
                for ($i = 5; $i >= 0; $i--) {
                    $monthStart = $now->copy()->subMonths($i)->startOfMonth();
                    $monthEnd = $now->copy()->subMonths($i)->endOfMonth();
                    $nouveauxUtilisateurs = User::where('role', '!=', 'admin')
                        ->whereBetween('created_at', [$monthStart, $monthEnd])
                        ->count();
                    $utilisateursActifs = User::where('role', '!=', 'admin')
                        ->whereBetween('updated_at', [$monthStart, $monthEnd])
                        ->count();
                    $variationNouveaux = $previousNouveaux > 0 ? (($nouveauxUtilisateurs - $previousNouveaux) / $previousNouveaux) * 100 : 0;
                    $variationActifs = $previousActifs > 0 ? (($utilisateursActifs - $previousActifs) / $previousActifs) * 100 : 0;
                    $evolution[] = [
                        'periode' => $monthStart->format('Y-m'),
                        'nouveaux_utilisateurs' => $nouveauxUtilisateurs,
                        'utilisateurs_actifs' => $utilisateursActifs,
                        'variation_nouveaux' => round($variationNouveaux, 2) . '%',
                        'variation_actifs' => round($variationActifs, 2) . '%',
                    ];
                    $previousNouveaux = $nouveauxUtilisateurs;
                    $previousActifs = $utilisateursActifs;
                }
                break;
        }

        return response()->json([
            'message' => 'Évolution des utilisateurs récupérée avec succès.',
            'data' => [
                'periode' => $period,
                'evolution' => $evolution,
            ]
        ]);
    }
}
