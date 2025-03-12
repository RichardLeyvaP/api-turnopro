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
        Schema::table('movement_product', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_out_id')->nullable()->change();
            $table->unsignedBigInteger('branch_int_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movement_product', function (Blueprint $table) {
            //
        });
    }
};
