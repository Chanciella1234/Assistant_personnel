<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // <-- NÉCESSAIRE pour utiliser DB::raw()

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Étape 1 : Ajouter les colonnes avec une valeur par défaut TEMPORAIRE.
        // Cela permet à MySQL de remplir les lignes existantes avec la date du jour (CURRENT_DATE)
        // au lieu de la date invalide '0000-00-00'.
        Schema::table('tasks', function (Blueprint $table) {
            $table->date('date_debut')->default(DB::raw('CURRENT_DATE'))->after('description');
            $table->date('date_fin')->default(DB::raw('CURRENT_DATE'))->after('date_debut');
        });

        // Étape 2 : Retirer la valeur par défaut.
        // Les colonnes restent NOT NULL, mais les futures insertions devront 
        // obligatoirement fournir une valeur via l'application (validation du contrôleur).
        Schema::table('tasks', function (Blueprint $table) {
            $table->date('date_debut')->change();
            $table->date('date_fin')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['date_debut', 'date_fin']);
        });
    }
};
