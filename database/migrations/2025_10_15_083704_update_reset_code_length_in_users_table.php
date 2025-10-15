<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécution de la migration.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // On s’assure que la colonne peut stocker un hash (jusqu’à 255 caractères)
            // et qu’elle peut rester vide si aucun code n’est encore généré
            $table->string('reset_code', 255)->nullable()->change();

            // On s’assure aussi que la colonne reset_code_expires_at existe et peut être nulle
            if (!Schema::hasColumn('users', 'reset_code_expires_at')) {
                $table->timestamp('reset_code_expires_at')->nullable();
            }
        });
    }

    /**
     * Annulation de la migration.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // On peut remettre la taille initiale si nécessaire (ex : 100)
            $table->string('reset_code', 100)->nullable()->change();
            $table->dropColumn('reset_code_expires_at');
        });
    }
};
