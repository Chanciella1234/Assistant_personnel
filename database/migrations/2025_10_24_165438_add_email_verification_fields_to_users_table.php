<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Columne pour stocker l'email en attente
            $table->string('pending_email')->nullable()->after('email');
            
            // Columne pour stocker le code de vérification à 6 chiffres
            $table->string('email_verification_code', 6)->nullable()->after('pending_email');
            
            // Columne pour stocker la date d'expiration du code (celle qui causait l'erreur)
            $table->timestamp('email_verification_expires_at')->nullable()->after('email_verification_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pending_email', 'email_verification_code', 'email_verification_expires_at']);
        });
    }
};
