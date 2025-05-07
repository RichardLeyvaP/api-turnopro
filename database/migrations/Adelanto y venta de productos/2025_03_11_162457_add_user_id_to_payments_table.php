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
            $table->unsignedBigInteger('user_id')->nullable(); // "nullable" si el campo puede ser nulo
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null'); // Relación con la tabla users
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['user_id']); // Eliminar la restricción de clave foránea
            $table->dropColumn('user_id'); // Eliminar el campo
        });
    }
};
