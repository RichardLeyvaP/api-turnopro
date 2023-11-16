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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();

            $table->date('data');//fecha
            $table->time('start_time');//cliente escoje la hora inicial que este disponible
            $table->time('final_hour');//la calcula el sistema con la suma de cada tiempo de cada servicio
            $table->time('total_time');//la calcula el sistema (final_hour - start_time)
            $table->boolean('from_home')->default(true);
            $table->unsignedBigInteger('car_id');
            $table->foreign('car_id')->references('id')->on('cars')->onDelete('cascade');           
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
