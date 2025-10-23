<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Activite;
use App\Models\Alerte;
use Illuminate\Support\Facades\Mail;
use App\Mail\AlerteMail;
use Carbon\Carbon;

class GenererAlertesCommand extends Command
{
    protected $signature = 'alertes:generer';
    protected $description = 'Génère et envoie les alertes automatiques pour les activités à venir';

    public function handle()
    {
        $now = Carbon::now();

        // 🔹 On récupère les activités à venir
        $activites = Activite::with('user')
            ->where('date_debut_activite', '>', $now)
            ->get();

        foreach ($activites as $activite) {
            $delaiDefaut = 10; // minutes
            $delaiPerso = $activite->rappel_personnalise;

            $debut = Carbon::parse($activite->date_debut_activite);

            // Vérifie si une alerte par défaut doit être envoyée
            if ($now->diffInMinutes($debut, false) <= $delaiDefaut && $now->diffInMinutes($debut, false) > 0) {
                $this->envoyerAlerte($activite, $delaiDefaut, 'defaut');
            }

            // Vérifie si une alerte personnalisée doit être envoyée
            if ($delaiPerso && $now->diffInMinutes($debut, false) <= $delaiPerso && $now->diffInMinutes($debut, false) > 0) {
                $this->envoyerAlerte($activite, $delaiPerso, 'personnalisee');
            }
        }

        $this->info('✅ Vérification et envoi des alertes terminés.');
    }

    private function envoyerAlerte($activite, $delai, $type)
    {
        // Vérifie si l’alerte a déjà été envoyée
        $existe = Alerte::where('activite_id', $activite->id)
            ->where('type', $type)
            ->where('delai_minutes', $delai)
            ->where('envoyee', true)
            ->first();

        // 🚫 Si elle existe déjà, on ne fait rien
        if ($existe) {
            return;
        }

        // ✅ Sinon, on l’envoie maintenant
        $message = ($type === 'defaut')
            ? "Rappel : Votre activité '{$activite->titre}' commence dans 10 minutes."
            : "Rappel personnalisé : Votre activité '{$activite->titre}' commence dans " . $delai . " minutes.";

        Mail::to($activite->user->email)->send(new AlerteMail($activite, $message));

        // 💾 On enregistre que cette alerte a été envoyée
        Alerte::create([
            'activite_id' => $activite->id,
            'user_id' => $activite->user_id,
            'type' => $type,
            'delai_minutes' => $delai,
            'message' => $message,
            'envoyee' => true,
        ]);
    }

}
