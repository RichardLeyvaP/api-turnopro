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
        Schema::table('retentions', function (Blueprint $table) {
            Schema::table('retentions', function (Blueprint $table) {
                $table->string('type')->default('Services');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retentions', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
