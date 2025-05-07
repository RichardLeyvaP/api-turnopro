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
        Schema::table('products', function (Blueprint $table) {
            $table->softDeletes(); // Agrega la columna deleted_at
        });

        // Agregar soft delete a la tabla product_store (tabla pivote)
        Schema::table('product_store', function (Blueprint $table) {
            $table->softDeletes(); // Agrega la columna deleted_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Revertir para product_store
        Schema::table('product_store', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
