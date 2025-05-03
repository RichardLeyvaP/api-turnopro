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
        Schema::table('cashier_box_closings', function (Blueprint $table) {
            Schema::table('cashier_box_closings', function (Blueprint $table) {
                $table->json('details')
                     ->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cashier_box_closings', function (Blueprint $table) {
            Schema::table('cashier_box_closings', function (Blueprint $table) {
                $table->dropColumn('details');
            });
        });
    }
};
