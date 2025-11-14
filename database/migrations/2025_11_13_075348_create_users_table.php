<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->string('password'); // Password Laravel
            $table->string('role')->default('user');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sap_login_at')->nullable();
            $table->string('last_sap_session_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
            
            $table->index('username');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};