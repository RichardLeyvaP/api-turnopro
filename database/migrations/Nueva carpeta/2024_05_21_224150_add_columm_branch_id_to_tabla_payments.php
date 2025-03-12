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
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('car_id')->nullable()->change();
            $table->unsignedBigInteger('branch_id')->nullable()->after('car_id');

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);

            // Eliminar la columna branch_id
            $table->dropColumn('branch_id');

            // Volver a hacer no nullable la columna car_id
            $table->unsignedBigInteger('car_id')->nullable(false)->change();
        });
    }
};
