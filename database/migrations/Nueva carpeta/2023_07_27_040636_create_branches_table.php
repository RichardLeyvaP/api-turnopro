<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name', 25)->unique();
            $table->string('phone', 15);
            $table->string('address', 250);
            $table->text('image_data')->nullable();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('business_type_id');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('restrict');
            $table->foreign('business_type_id')->references('id')->on('business_types')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};