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
        Schema::table('cashier_box_closings', function (Blueprint $table) {
            $table->foreignId('box_close_id')
                  ->nullable()
                  ->constrained('box_closes') // Asume que la tabla relacionada se llama 'boxes'
                  ->nullOnDelete()
                  ->comment('Referencia al cierre de caja relacionado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cashier_box_closings', function (Blueprint $table) {
            $table->dropForeign(['box_close_id']);
            $table->dropColumn('box_close_id');
        });
    }
};
