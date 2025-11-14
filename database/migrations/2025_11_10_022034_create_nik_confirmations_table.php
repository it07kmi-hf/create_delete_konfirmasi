<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nik_confirmations', function (Blueprint $table) {
            $table->id();
            
            // Personnel & Plant Info
            $table->string('pernr', 8)->comment('Personnel Number');
            $table->string('werks', 4)->comment('Plant');
            $table->string('name1', 100)->nullable()->comment('Employee Name'); // ✅ Updated to 100
            $table->string('created_by', 12)->nullable()->comment('Created By User');
            $table->date('created_on')->nullable()->comment('Creation Date');
            
            // Sync Info
            $table->timestamp('synced_at')->nullable()->comment('Last Sync from SAP');
            $table->timestamps();
            
            // Indexes
            $table->unique(['pernr', 'werks'], 'unique_pernr_werks');
            $table->index('pernr', 'idx_pernr');
            $table->index('werks', 'idx_werks');
            $table->index('created_on', 'idx_created_on');
            $table->index('synced_at', 'idx_synced_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nik_confirmations');
    }
};