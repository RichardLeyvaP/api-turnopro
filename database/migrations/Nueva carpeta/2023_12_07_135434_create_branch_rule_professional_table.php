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
        Schema::create('branch_rule_professional', function (Blueprint $table) {
            $table->id();
            $table->date('data');
            $table->boolean('estado');

            $table->unsignedBigInteger('branch_rule_id');
            $table->unsignedBigInteger('professional_id');

            $table->foreign('branch_rule_id')->references('id')->on('branch_rule')->onDelete('cascade');
            $table->foreign('professional_id')->references('id')->on('professionals')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_rule_professional');
    }
};
