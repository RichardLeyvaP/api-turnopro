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
        Schema::table('product_categories', function (Blueprint $table) {
            $table->boolean('gives_commission')
                  ->default(0)
                  ->nullable()
                  ->comment('Indica si esta categoría genera comisión');
        });
        // Agregar campo commission_rate a products
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)
                  ->nullable()
                  ->after('sale_price') // Ajusta la posición según necesites
                  ->comment('Porcentaje de comisión que se aplica sobre el precio del producto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropColumn('gives_commission');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('commission_rate');
        });

    }
};
