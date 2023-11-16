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
        Schema::create('branch_service_professional', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('branch_service_id');
            $table->unsignedBigInteger('professional_id');

            $table->foreign('branch_service_id')->references('id')->on('branch_service')->onDelete('cascade');
            $table->foreign('professional_id')->references('id')->on('professionals')->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_service_professional');
    }
};
