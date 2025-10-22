<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alertes', function (Blueprint $table) {
            $table->id();

            // 🔗 Activité concernée
            $table->foreignId('activite_id')->constrained()->onDelete('cascade');

            // 🔗 Utilisateur qui reçoit
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // 🔹 Type d’alerte (ex: "par défaut" ou "personnalisée")
            $table->enum('type', ['defaut', 'personnalisee'])->default('defaut');

            // 🔹 Délai avant activité (en minutes)
            $table->integer('delai_minutes')->default(10);

            // 🔹 Indique si l’alerte a été envoyée
            $table->boolean('envoyee')->default(false);

            // 🔹 Contenu du message envoyé
            $table->text('message')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertes');
    }
};
