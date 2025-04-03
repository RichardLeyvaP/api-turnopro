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
        Schema::create('monthly_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->foreignId('business_id')->nullable()->constrained('businesses')->onDelete('cascade');
            $table->decimal('available_money', 15, 2)->comment('Dinero disponible')->default(0)->nullable();
            $table->decimal('utility', 15, 2)->comment('Utilidad bruta')->default(0)->nullable();
            $table->decimal('retention', 15, 2)->default(0)->comment('Retención')->nullable();
            $table->decimal('discounts', 15, 2)->default(0)->comment('Descuentos aplicados')->nullable();
            $table->decimal('differences', 15, 2)->default(0)->comment('Diferencias encontradas')->nullable();
            $table->decimal('net_utility', 15, 2)->comment('Utilidad neta')->default(0)->nullable();
            $table->string('month', 7)->nullable()->comment('Formato YYYY-MM');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('data')->nullable();
            
            // Nuevos campos JSON
            $table->json('incomes')->nullable()->comment('Array de ingresos {id, name, amount}');
            $table->json('expenses')->nullable()->comment('Array de gastos {id, name, amount}');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_closures');
    }
};
