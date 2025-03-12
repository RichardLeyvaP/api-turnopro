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
        Schema::create('cashiersales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('professional_id');
            $table->unsignedBigInteger('product_store_id');
            $table->date('data');
            $table->decimal('price', 14, 2);
            $table->integer('pay')->nullable()->default(0);
            $table->decimal('percent_wint', 14, 2)->nullable()->default(0);
            $table->integer('cant')->nullable()->default(0);            
            $table->integer('paycashier')->nullable()->default(0);            
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('professional_id')->references('id')->on('professionals');
            $table->foreign('product_store_id')->references('id')->on('product_store');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashiersales');
    }
};
