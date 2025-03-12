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
        Schema::create('movement_product', function (Blueprint $table) {
            $table->id();
            $table->date('data');
            $table->integer('product_id');
            $table->integer('branch_out_id');
            $table->integer('store_out_id');            
            $table->integer('store_out_exit');
            $table->integer('branch_int_id');
            $table->integer('store_int_id');            
            $table->integer('store_int_exit');
            $table->integer('cant');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movement_product');
    }
};
