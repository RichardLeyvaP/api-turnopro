<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            DB::statement('ALTER TABLE products MODIFY purchase_price DOUBLE(8,2)');
            DB::statement('ALTER TABLE products MODIFY sale_price DOUBLE(8,2)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            DB::statement('ALTER TABLE products MODIFY purchase_price DECIMAL(8,2)');
            DB::statement('ALTER TABLE products MODIFY sale_price DECIMAL(8,2)');
        });
    }
};
