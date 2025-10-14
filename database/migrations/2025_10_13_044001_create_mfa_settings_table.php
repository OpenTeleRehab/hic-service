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
        Schema::create('mfa_settings', function (Blueprint $table) {
            $table->id();
            $table->json('user_type');
            $table->string('mfa_enforcement');
            $table->string('mfa_expiration_duration');
            $table->string('skip_mfa_setup_duration');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mfa_settings');
    }
};
