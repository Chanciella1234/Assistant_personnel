<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ajoute 'pause' à la liste des statuts autorisés : ['en attente', 'en cours', 'terminee', 'pause'].
     */
    public function up(): void
    {
        // Les ENUM sont difficiles à modifier avec le Schema Builder, on utilise donc DB::statement.
        $newStatutEnum = "'en attente', 'en cours', 'terminee', 'pause'";
        
        // Modifier la colonne dans la base de données
        DB::statement("ALTER TABLE tasks MODIFY statut ENUM({$newStatutEnum}) NOT NULL DEFAULT 'en attente'");
    }

    /**
     * Reverse the migrations.
     * Retire 'pause' de la liste des statuts.
     */
    public function down(): void
    {
        $oldStatutEnum = "'en attente', 'en cours', 'terminee'";
        
        DB::statement("ALTER TABLE tasks MODIFY statut ENUM({$oldStatutEnum}) NOT NULL DEFAULT 'en attente'");
    }
};
