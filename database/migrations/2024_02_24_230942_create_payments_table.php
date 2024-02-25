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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('car_id');
            $table->decimal('cash', 8, 2)->nullable();
            $table->decimal('creditCard', 8, 2)->nullable();
            $table->decimal('debit', 8, 2)->nullable();
            $table->decimal('transfer', 8, 2)->nullable();
            $table->decimal('other', 8, 2)->nullable();
            $table->foreign('car_id')->references('id')->on('cars')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
