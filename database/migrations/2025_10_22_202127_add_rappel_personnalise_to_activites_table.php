<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('activites', function (Blueprint $table) {
            $table->integer('rappel_personnalise')
                ->nullable()
                ->after('statut')
                ->comment('Délai personnalisé avant activité (en minutes)');
        });
    }

    public function down(): void
    {
        Schema::table('activites', function (Blueprint $table) {
            $table->dropColumn('rappel_personnalise');
        });
    }
};
