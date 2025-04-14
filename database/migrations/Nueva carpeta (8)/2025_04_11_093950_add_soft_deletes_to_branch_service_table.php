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
        // Para branch_service
        Schema::table('branch_service', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Para branch_service_professional
        Schema::table('branch_service_professional', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir para branch_service
        Schema::table('branch_service', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Revertir para branch_service_professional
        Schema::table('branch_service_professional', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
