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
        Schema::table('notifications', function (Blueprint $table) {
            $table->integer('stateCajero')->default(0)->nullable();
            $table->integer('stateAdmSucur')->default(0)->nullable();
            $table->integer('stateAdm')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('stateCajero');
            $table->dropColumn('stateAdmSucur');
            $table->dropColumn('stateAdm');
        });
    }
};
