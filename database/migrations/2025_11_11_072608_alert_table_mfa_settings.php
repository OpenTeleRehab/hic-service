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
        Schema::table('mfa_settings', function (Blueprint $table) {
            $table->integer('mfa_expiration_duration')->nullable()->change();
            $table->integer('skip_mfa_setup_duration')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mfa_settings', function (Blueprint $table) {
            $table->string('mfa_expiration_duration')->change();
            $table->string('skip_mfa_setup_duration')->change();
        });
    }
};
