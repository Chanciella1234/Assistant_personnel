<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('commentaires', function (Blueprint $table) {
            $table->id();

            // 🔹 L’utilisateur qui commente
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // 🔹 Contenu du commentaire
            $table->text('contenu');

            // 🔹 Date de création automatique
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commentaires');
    }
};
