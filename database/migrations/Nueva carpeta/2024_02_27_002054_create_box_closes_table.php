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
        Schema::create('box_closes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('box_id');
            $table->date('data');
            $table->decimal('totalMount', 8, 2)->nullable();
            $table->decimal('totalService', 8, 2)->nullable();
            $table->decimal('totalProduct', 8, 2)->nullable();
            $table->decimal('totalTip', 8, 2)->nullable();
            $table->decimal('totalCash', 8, 2)->nullable();
            $table->decimal('totalDebit', 8, 2)->nullable();
            $table->decimal('totalCreditCard', 8, 2)->nullable();
            $table->decimal('totalTransfer', 8, 2)->nullable();
            $table->decimal('totalOther', 8, 2)->nullable();
            $table->decimal('totalCardGif', 8, 2)->nullable();
            $table->foreign('box_id')->references('id')->on('boxes')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('box_closes');
    }
};
