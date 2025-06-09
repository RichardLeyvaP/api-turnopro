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
        Schema::table('finances', function (Blueprint $table) {
            $table->unsignedBigInteger('professional_payment_id')
                  ->nullable(); // Opcional: especifica posición del campo
            
            $table->foreign('professional_payment_id')
                  ->references('id')
                  ->on('professionals_payments')
                  ->onDelete('set null'); // O 'cascade' según tu necesidad
            
            // Agregar operation_tip_id como nullable
            $table->unsignedBigInteger('operation_tip_id')
                  ->nullable()
                  ->after('professional_payment_id'); // Colocarlo después del campo relacionado
            
            $table->foreign('operation_tip_id')
                  ->references('id')
                  ->on('operation_tip')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finances', function (Blueprint $table) {
           // Eliminar la clave foránea primesro
           $table->dropForeign(['professional_payment_id']);
            
           // Eliminar la columna
           $table->dropColumn('professional_payment_id');

           $table->dropForeign(['operation_tip_id']);
            
            // Eliminar la columna
            $table->dropColumn('operation_tip_id');
        });
    }
};
