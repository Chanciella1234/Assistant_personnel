<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('taches', function (Blueprint $table) {
            $table->id();

            // 🔹 Relation avec activité
            $table->foreignId('activite_id')->constrained()->onDelete('cascade');

            // 🔹 Informations principales
            $table->string('titre');
            $table->dateTime('date_debut_tache');
            $table->dateTime('date_fin_tache');

            // 🔹 Statut (même logique que les activités)
            $table->enum('statut', ['en attente', 'en cours', 'pause', 'terminee'])
                  ->default('en attente');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taches');
    }
};
