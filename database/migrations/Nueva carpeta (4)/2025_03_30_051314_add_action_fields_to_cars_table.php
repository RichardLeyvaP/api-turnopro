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
        Schema::table('cars', function (Blueprint $table) {
            $table->integer('action_status')
          ->default(0);
          
            $table->json('action_descriptions')->nullable();
            $table->json('change_log')->nullable();
            $table->json('payment')->nullable(); // Colócalo después del campo que consideres apropiado
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn(['action_status', 'action_descriptions', 'change_log', 'payment']);
        });
    }
};
