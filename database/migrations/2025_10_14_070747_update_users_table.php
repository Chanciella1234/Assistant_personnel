<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // $table->string('name')->after('id');
            $table->enum('role', ['user', 'admin'])->default('user')->after('email');
            $table->boolean('is_verified')->default(false)->after('role');
            $table->string('verification_token')->nullable()->after('is_verified');
            $table->integer('rappel_personnalise')->nullable()->comment('minutes')->after('verification_token');
            $table->string('langue')->default('fr')->after('rappel_personnalise');
            $table->string('theme')->default('clair')->after('langue');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['name','role','is_verified','verification_token','rappel_personnalise','langue','theme']);
        });
    }
};
