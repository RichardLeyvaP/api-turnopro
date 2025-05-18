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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('branch_id')
                  ->nullable()
                  ->constrained('branches')
                  ->nullOnDelete()
                  ->comment('ID de la sucursal relacionada');
                  
            $table->foreignId('professional_id')
                  ->nullable()
                  ->after('branch_id')
                  ->constrained('professionals')
                  ->nullOnDelete()
                  ->comment('ID del profesional relacionado');
            $table->decimal('commission_rate', 5, 2)
                  ->nullable()
                  ->comment('Porcentaje de comisión aplicado en la orden');
                  
            $table->decimal('commission_amount', 14, 2)
                  ->nullable()
                  ->after('commission_rate')
                  ->comment('Monto total de comisión generada en la orden');
            $table->integer('paycashier')
                  ->default(0)
                  ->after('commission_amount')
                  ->comment('Indica si ha sido pagado al professional por la venta del producto');
        });

        // Agregar campos a la tabla cashier_sales
        Schema::table('cashiersales', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)
                  ->nullable()
                  ->comment('Porcentaje de comisión aplicado en la venta');
                  
            $table->decimal('commission_amount', 14, 2)
                  ->nullable()
                  ->after('commission_rate')
                  ->comment('Monto total de comisión generada en la venta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['professional_id']);
            $table->dropColumn([
                'branch_id',
                'professional_id',
                'commission_rate',
                'commission_amount',
                'paycashier'
            ]);
        });

        // Eliminar campos de la tabla cashier_sales
        Schema::table('cashiersales', function (Blueprint $table) {
            $table->dropColumn(['commission_rate', 'commission_amount']);
        });
    }
};
