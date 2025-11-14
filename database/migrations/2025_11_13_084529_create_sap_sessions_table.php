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
        Schema::create('sap_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('laravel_session_id')->unique();
            $table->string('sap_username');
            $table->text('sap_password_encrypted');
            $table->string('user_display')->nullable();
            $table->timestamp('logged_in_at')->nullable();
            $table->timestamp('last_validated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Indexes for better performance
            $table->index('laravel_session_id');
            $table->index('sap_username');
            $table->index('expires_at');
            $table->index(['laravel_session_id', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sap_sessions');
    }
};