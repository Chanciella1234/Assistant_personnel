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
        Schema::table('activities', function (Blueprint $table) {
            if (!Schema::hasColumn('activities', 'paused_at')) {
                $table->timestamp('paused_at')->nullable()->after('statut');
            }
            if (!Schema::hasColumn('activities', 'total_pause_seconds')) {
                $table->integer('total_pause_seconds')->default(0)->after('paused_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['paused_at', 'total_pause_seconds']);
        });
    }

};
