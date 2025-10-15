<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('reset_code', 6)->nullable()->after('code_activation');
            $table->timestamp('reset_code_expires_at')->nullable()->after('reset_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['reset_code', 'reset_code_expires_at']);
        });
    }
};
