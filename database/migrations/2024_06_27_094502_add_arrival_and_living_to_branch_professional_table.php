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
        Schema::table('branch_professional', function (Blueprint $table) {
            $table->integer('arrival')->nullable();
            $table->integer('living')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_professional', function (Blueprint $table) {
            $table->dropColumn('arrival');
            $table->dropColumn('living');
        });
    }
};
