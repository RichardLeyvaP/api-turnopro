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
        Schema::table('tails', function (Blueprint $table) {
            $table->tinyInteger('timeClock')->default(0)->after('clock');
            $table->tinyInteger('detached')->default(0)->after('timeClock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tails', function (Blueprint $table) {
            $table->dropColumn('timeClock');
            $table->dropColumn('detached');
        });
    }
};