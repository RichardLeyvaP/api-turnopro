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
        Schema::create('productsales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_store_id')->nullable();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('enrollment_id')->nullable();
            $table->decimal('price', 14, 2);
            $table->date('data');
            $table->integer('cant')->nullable();
            

            $table->foreign('product_store_id')->references('id')->on('product_store')->onDelete('set null');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('set null');
            $table->foreign('enrollment_id')->references('id')->on('enrollments')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productsales');
    }
};
