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
        Schema::table('courses', function (Blueprint $table) {
            $table->integer('total_enrollment');
            $table->integer('available_slots');
            $table->decimal('reservation_price', 8, 2); // Asumiendo que quieres dos decimales de precisión
            $table->integer('duration'); // O considera otro tipo de datos según tu necesidad
            $table->integer('practical_percentage'); // Cambiado a entero
            $table->integer('theoretical_percentage'); // Cambiado a entero
            });
    
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            //
        });
    }
};
