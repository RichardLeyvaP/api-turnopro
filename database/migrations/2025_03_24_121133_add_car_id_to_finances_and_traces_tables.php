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
            // Agregar a la tabla finances
        Schema::table('finances', function (Blueprint $table) {
            $table->unsignedBigInteger('car_id')
                  ->nullable()
                  ->after('id'); // Opcional: define la posición de la columna
        });

        // Agregar a la tabla traces
        Schema::table('traces', function (Blueprint $table) {
            $table->unsignedBigInteger('car_id')
                  ->nullable()
                  ->after('id'); // Opcional: define la posición de la columna
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
          // Eliminar de finances
          Schema::table('finances', function (Blueprint $table) {
            $table->dropColumn('car_id');
        });

        // Eliminar de traces
        Schema::table('traces', function (Blueprint $table) {
            $table->dropColumn('car_id');
        });
    }
};
