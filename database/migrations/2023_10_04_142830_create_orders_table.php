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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('car_id');
            $table->unsignedBigInteger('product_store_id')->nullable();
            $table->unsignedBigInteger('branch_service_person_id')->nullable();
            $table->boolean('is_product')->unsigned();
            $table->decimal('price', 8, 2);
            $table->boolean('request_delete')->default(false);

            $table->foreign('car_id')->references('id')->on('cars')->onDelete('cascade');
            $table->foreign('product_store_id')->references('id')->on('product_store')->onDelete('set null');
            $table->foreign('branch_service_person_id')->references('id')->on('branch_service_person')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
