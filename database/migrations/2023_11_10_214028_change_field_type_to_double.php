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
        Schema::table('services', function (Blueprint $table) {
            DB::statement('ALTER TABLE services MODIFY price_service DOUBLE(8,2)');
            DB::statement('ALTER TABLE services MODIFY profit_percentaje DOUBLE(8,2)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            DB::statement('ALTER TABLE services MODIFY price_service DECIMAL(8,2)');
            DB::statement('ALTER TABLE services MODIFY profit_percentaje DECIMAL(8,2)');
        });
    }
};
