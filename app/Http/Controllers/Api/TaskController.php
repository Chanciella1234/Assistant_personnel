<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TaskController extends Controller
{
    /**
     * ⚙️ Fonction interne pour récupérer et vérifier la tâche
     */
    private function getTask(int $activityId, int $taskId)
    {
        $user = Auth::user();
        // 1. Vérifier que l'activité appartient à l'utilisateur
        $activity = Activity::where('user_id', $user->id)->findOrFail($activityId);
        // 2. Récupérer la tâche dans le contexte de cette activité
        $task = $activity->tasks()->findOrFail($taskId);
        
        return $task;
    }

    /**
     * 🟢 Lister les tâches d’une activité
     */
    public function index($activityId)
    {
        $user = Auth::user();

        // Vérifier que l'activité appartient à l'utilisateur
        $activity = Activity::where('user_id', $user->id)->findOrFail($activityId);

        return response()->json($activity->tasks);
    }

    /**
     * 🟢 Ajouter une tâche à une activité
     */
    public function store(Request $request, $activityId)
    {
        $user = Auth::user();
        $activity = Activity::where('user_id', $user->id)->findOrFail($activityId);

        $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            // 'statut' => 'in:en attente,en cours,terminee,pause',
        ]);

        $task = new Task($request->all());
        $task->activity_id = $activity->id;
        $activity->statut = 'en attente';
        $task->paused_at = null; // Initialisation
        $task->total_pause_seconds = 0; // Initialisation
        $task->save();

        return response()->json([
            'message' => 'Tâche ajoutée avec succès',
            'data' => $task
        ], 201);
    }

    /**
     * 🟢 Afficher une tâche spécifique
     */
    public function show($activityId, $taskId)
    {
        $task = $this->getTask($activityId, $taskId);
        return response()->json($task);
    }

    /**
     * 🟢 Modifier une tâche
     */
    public function update(Request $request, $activityId, $taskId)
    {
        $task = $this->getTask($activityId, $taskId);

        $request->validate([
            'titre' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'date_debut' => 'sometimes|required|date', 
            'date_fin' => 'sometimes|required|date|after_or_equal:date_debut', 
            'statut' => 'in:en attente,en cours,terminee,pause',
        ]);

        $task->update($request->all());

        return response()->json([
            'message' => 'Tâche mise à jour avec succès',
            'data' => $task
        ]);
    }

    /**
     * 🟢 Supprimer une tâche
     */
    public function destroy($activityId, $taskId)
    {
        $task = $this->getTask($activityId, $taskId);
        $task->delete();

        return response()->json(['message' => 'Tâche supprimée avec succès']);
    }

    /**
     * 🟠 Mettre une tâche en pause
     */
    public function pause($activityId, $taskId)
    {
        $task = $this->getTask($activityId, $taskId);

        if ($task->statut !== 'en cours') {
            return response()->json(['message' => 'Impossible de mettre en pause une tâche non en cours.'], 400);
        }

        $task->statut = 'pause';
        $task->paused_at = Carbon::now();
        $task->save();

        return response()->json([
            'message' => 'Tâche mise en pause avec succès.',
            'data' => $task
        ]);
    }

    /**
     * 🟢 Reprendre une tâche mise en pause
     */
    public function resume($activityId, $taskId)
    {
        $task = $this->getTask($activityId, $taskId);

        if ($task->statut !== 'pause') {
            return response()->json(['message' => 'Impossible de reprendre une tâche qui n\'est pas en pause.'], 400);
        }

        $now = Carbon::now();
        if ($task->paused_at) {
            $diff = $now->diffInSeconds(Carbon::parse($task->paused_at));
            $task->total_pause_seconds = ($task->total_pause_seconds ?? 0) + $diff;
        }

        $task->paused_at = null;
        $task->statut = 'en cours';
        $task->save();

        return response()->json([
            'message' => 'Tâche reprise avec succès.',
            'data' => $task
        ]);
    }
}
