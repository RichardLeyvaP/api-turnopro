<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reservation_id');//insertar en esta tabla ordenado por start_time
            $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('cascade');
            $table->timestamps();
            // Reiniciar el valor del ID al reiniciar la tabla
            DB::statement('ALTER TABLE tails AUTO_INCREMENT = 1');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tails');
    }
};
