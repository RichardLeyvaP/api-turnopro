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
        Schema::create('worker_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_id')->constrained('professionals')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->date('data');
            $table->decimal('price', 14, 2); // Precio original del producto
            $table->decimal('discount', 5, 2); // Porcentaje de descuento aplicado
            $table->integer('cant'); // Cantidad de productos
            $table->decimal('total', 14, 2); // Total con descuento aplicado
            $table->decimal('percent_wint', 14, 2)->nullable()->default(0);
            $table->date('discount_date')->nullable(); // Fecha cuando se aplicó el descuento
            $table->tinyInteger('status')->default(0); // 0 = pendiente, 1 = completado, etc.
            $table->timestamps();
            
            // Índices para mejorar el rendimientso en búsquedas
            $table->index(['professional_id', 'branch_id']);
            $table->index('data');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_purchases');
    }
};
