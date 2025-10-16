<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('titre');
            $table->text('description')->nullable();
            $table->date('date_activite');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->enum('priorite', ['faible', 'moyenne', 'forte'])->default('moyenne');
            $table->enum('statut', ['en attente', 'en cours', 'terminee'])->default('en attente');
            $table->integer('rappel_personnalise')->nullable()->comment('minutes avant l’activité');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
