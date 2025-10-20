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
        Schema::table('tasks', function (Blueprint $table) {
            // Horodatage de la dernière pause (null si non en pause)
            $table->timestamp('paused_at')->nullable()->after('statut');
            // Temps total passé en pause (en secondes)
            $table->integer('total_pause_seconds')->default(0)->after('paused_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['paused_at', 'total_pause_seconds']);
        });
    }
};
