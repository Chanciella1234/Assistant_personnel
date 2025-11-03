<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commentaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentaireController extends Controller
{
    /**
     * 📋 Liste de tous les commentaires (avec noms des utilisateurs, sans emails)
     */
    public function index()
    {
        $commentaires = Commentaire::with('user:id,name')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($commentaire) {
                return [
                    'id' => $commentaire->id,
                    'contenu' => $commentaire->contenu,
                    'created_at' => $commentaire->created_at,
                    'updated_at' => $commentaire->updated_at,
                    'date_commentaire' => $commentaire->date_commentaire,
                    'user' => [
                        'id' => $commentaire->user->id,
                        'name' => $commentaire->user->name,
                    ],
                ];
            });

        return response()->json([
            'message' => 'Liste des commentaires récupérée avec succès.',
            'data' => $commentaires,
        ]);
    }

    /**
     * 🟢 Créer un commentaire
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'contenu' => 'required|string|min:3',
        ]);

        $commentaire = Commentaire::create([
            'user_id' => Auth::id(),
            'contenu' => $validated['contenu'],
        ]);

        return response()->json([
            'message' => 'Commentaire ajouté avec succès.',
            'data' => [
                'id' => $commentaire->id,
                'contenu' => $commentaire->contenu,
                'date_commentaire' => $commentaire->date_commentaire,
            ],
        ], 201);
    }

    /**cf
     * 🟡 Modifier un commentaire (uniquement par son auteur)
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $commentaire = Commentaire::where('user_id', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'contenu' => 'required|string|min:3',
        ]);

        $commentaire->update($validated);

        return response()->json([
            'message' => 'Commentaire mis à jour avec succès.',
            'data' => [
                'id' => $commentaire->id,
                'contenu' => $commentaire->contenu,
                'date_commentaire' => $commentaire->date_commentaire,
            ],
        ]);
    }

    /**
     * 🗑️ Supprimer un commentaire (par son auteur)
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $commentaire = Commentaire::where('user_id', $user->id)->findOrFail($id);
        $commentaire->delete();

        return response()->json([
            'message' => 'Commentaire supprimé avec succès.',
        ]);
    }

    /**
     * 👑 Liste de tous les commentaires pour les admins (avec noms des utilisateurs)
     */
    public function adminIndex()
    {
        // Vérifier que l'utilisateur est admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'message' => 'Accès refusé. Réservé aux administrateurs.',
            ], 403);
        }

        $commentaires = Commentaire::with('user:id,name,email')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($commentaire) {
                return [
                    'id' => $commentaire->id,
                    'contenu' => $commentaire->contenu,
                    'created_at' => $commentaire->created_at,
                    'updated_at' => $commentaire->updated_at,
                    'date_commentaire' => $commentaire->date_commentaire,
                    'user' => [
                        'id' => $commentaire->user->id,
                        'name' => $commentaire->user->name,
                        'email' => $commentaire->user->email,
                    ],
                ];
            });

        return response()->json([
            'message' => 'Liste de tous les commentaires récupérée avec succès.',
            'data' => $commentaires,
        ]);
    }
}
