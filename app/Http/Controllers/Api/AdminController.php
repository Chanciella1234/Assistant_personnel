<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * 🧩 Vérifie si l’utilisateur connecté est bien admin
     */
    private function ensureAdmin($user)
    {
        if ($user->role !== 'admin') {
            abort(403, 'Accès refusé : réservé à l’administrateur.');
        }
    }

    /**
     * 👥 Liste de tous les utilisateurs
     */
    public function index()
    {
        $admin = auth()->user();
        $this->ensureAdmin($admin);

        $utilisateurs = User::select('id', 'name', 'email', 'langue', 'theme', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Liste des utilisateurs récupérée avec succès.',
            'data' => $utilisateurs,
        ]);
    }

    /**
     * 🔍 Détails d’un utilisateur : ses activités, tâches et commentaires
     */
    public function show($id)
    {
        $admin = auth()->user();
        $this->ensureAdmin($admin);

        $utilisateur = User::with([
            'activites.taches', // les tâches de chaque activité
            'activites' => function ($query) {
                $query->select('id', 'user_id', 'titre', 'description', 'date_debut_activite', 'date_fin_activite', 'priorite', 'statut');
            },
            'activites.taches' => function ($query) {
                $query->select('id', 'activite_id', 'titre', 'date_debut_tache', 'date_fin_tache', 'statut');
            },
            'commentaires' => function ($query) {
                $query->select('id', 'user_id', 'contenu', 'created_at');
            },
        ])
            ->select('id', 'name', 'email', 'langue', 'theme', 'created_at')
            ->findOrFail($id);

        return response()->json([
            'message' => 'Détails de l’utilisateur récupérés avec succès.',
            'data' => $utilisateur,
        ]);
    }
}
