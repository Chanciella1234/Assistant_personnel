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
        DB::statement("ALTER TABLE activities MODIFY statut ENUM({$newStatutEnum}) NOT NULL DEFAULT 'en attente'");
        
        // Note: Si vous aviez d'autres contraintes (commentaires, etc.), elles devraient être répétées ici.
    }

    /**
     * Reverse the migrations.
     * Retire 'pause' de la liste des statuts.
     */
    public function down(): void
    {
        // En cas d'annulation, nous retirons la valeur 'pause'.
        // ATTENTION : Cette opération échouera si des lignes existent avec le statut 'pause'.
        $oldStatutEnum = "'en attente', 'en cours', 'terminee'";
        
        DB::statement("ALTER TABLE activities MODIFY statut ENUM({$oldStatutEnum}) NOT NULL DEFAULT 'en attente'");
    }
};
