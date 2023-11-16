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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->boolean('simultaneou')->unsigned();
            $table->decimal('price_service', 8, 2);
            $table->enum('type_service', ['Regular','Especial']);
            $table->decimal('profit_percentaje', 8, 2);
            $table->integer('duration_service');
            $table->string('image_service')->nullable();
            $table->string('service_comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
