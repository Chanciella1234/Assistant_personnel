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
}
