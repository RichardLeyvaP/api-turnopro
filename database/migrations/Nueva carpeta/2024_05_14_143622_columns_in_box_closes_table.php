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
        Schema::table('box_closes', function (Blueprint $table) {
            $table->decimal('totalMount', 14, 2)->nullable()->change();
            $table->decimal('totalService', 14, 2)->nullable()->change();
            $table->decimal('totalProduct', 14, 2)->nullable()->change();
            $table->decimal('totalTip', 14, 2)->nullable()->change();
            $table->decimal('totalCash', 14, 2)->nullable()->change();
            $table->decimal('totalDebit', 14, 2)->nullable()->change();
            $table->decimal('totalCreditCard', 14, 2)->nullable()->change();
            $table->decimal('totalTransfer', 14, 2)->nullable()->change();
            $table->decimal('totalOther', 14, 2)->nullable()->change();
            $table->decimal('totalCardGif', 14, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('box_closes', function (Blueprint $table) {
            //
        });
    }
};
