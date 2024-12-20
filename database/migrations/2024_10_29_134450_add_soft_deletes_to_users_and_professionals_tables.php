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
        // Agregar soft deletes a la tabla users
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes(); // Agrega el campo deleted_at
        });

        // Agregar soft deletes a la tabla professionals
        Schema::table('professionals', function (Blueprint $table) {
            $table->softDeletes(); // Agrega el campo deleted_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         // Eliminar soft deletes de la tabla users
         Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes(); // Elimina el campo deleted_at
        });

        // Eliminar soft deletes de la tabla professionals
        Schema::table('professionals', function (Blueprint $table) {
            $table->dropSoftDeletes(); // Elimina el campo deleted_at
        });
    }
};
