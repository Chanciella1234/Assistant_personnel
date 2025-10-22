<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commentaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentaireController extends Controller
{
    /**
     * 📋 Liste des commentaires de l’utilisateur connecté
     */
    public function index()
    {
        $user = Auth::user();

        $commentaires = Commentaire::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get(['id', 'contenu', 'created_at', 'updated_at']); // on récupère aussi les dates

        return response()->json([
            'message' => 'Liste de vos commentaires récupérée avec succès.',
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

    /**
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
}
