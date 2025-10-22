<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activites', function (Blueprint $table) {
            $table->id();

            // 🔹 L'utilisateur propriétaire
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('titre');
            $table->text('description')->nullable();

            $table->dateTime('date_debut_activite');
            $table->dateTime('date_fin_activite');

            $table->enum('priorite', ['faible', 'moyenne', 'forte'])->default('moyenne');
            
            $table->enum('statut', ['en attente', 'en cours', 'pause', 'terminee'])
                  ->default('en attente');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activites');
    }
};
