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
        Schema::create('operation_tip', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();            
            $table->unsignedBigInteger('professional_id')->nullable();
            $table->date('date');
            $table->decimal('amount', 14, 2);
            $table->string('type')->nullable();
            $table->decimal('coffe_percent', 14, 2)->nullable();
            $table->foreign('professional_id')->references('id')->on('professionals')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operation_tip');
    }
};
