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
        Schema::create('cashier_box_closings', function (Blueprint $table) {
            $table->id(); // ID automático
            $table->decimal('totalTip', 15, 2)->nullable(); // Propinas totales
            $table->decimal('totalProduct', 15, 2)->nullable(); // Total de productos
            $table->decimal('totalService', 15, 2)->nullable(); // Total de servicios
            $table->decimal('totalCash', 15, 2)->nullable(); // Total en efectivo
            $table->decimal('totalCreditCard', 15, 2)->nullable(); // Total en tarjeta de crédito
            $table->decimal('totalDebit', 15, 2)->nullable(); // Total en tarjeta de débito
            $table->decimal('totalTransfer', 15, 2)->nullable(); // Total en transferencias
            $table->decimal('totalOther', 15, 2)->nullable(); // Otros totales
            $table->decimal('totalMount', 15, 2)->nullable(); // Monto total
            $table->decimal('totalCardGif', 15, 2)->nullable(); // Total en tarjetas de regalo
            $table->decimal('existence', 15, 2)->nullable(); // Existencia
            $table->decimal('cashFound', 15, 2)->nullable(); // Efectivo encontrado
            $table->decimal('extraccion', 15, 2)->nullable(); // Extracción
            $table->unsignedBigInteger('branch_id')->nullable(); // ID de la sucursal
            $table->date('data')->nullable()->useCurrent(); // Datos adicionales en formato JSON
            $table->decimal('adelanto', 15, 2)->nullable(); // Adelanto
            $table->decimal('bonos', 15, 2)->nullable(); // Bonos
            $table->decimal('diferencia', 15, 2)->nullable(); // Diferencia
            $table->unsignedBigInteger('user_id')->nullable(); // ID del usuario
            $table->text('description')->nullable(); // Descripción del cierre de caja
            $table->timestamps(); // created_at y updated_at

            // Claves foráneas
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_box_closings');
    }
};
