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
        Schema::create('professional_workplace', function (Blueprint $table) {
            $table->id();
            $table->date('data');
            $table->string('places')->nullable();

            $table->unsignedBigInteger('professional_id');
            $table->unsignedBigInteger('workplace_id');

            $table->foreign('professional_id')->references('id')->on('professionals')->onDelete('cascade');
            $table->foreign('workplace_id')->references('id')->on('workplaces')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('professional_workplace');
    }
};
