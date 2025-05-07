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
        Schema::create('advances', function (Blueprint $table) {
            $table->id();
            $table->date('data');
            $table->foreignId('professional_id')->constrained('professionals')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('type')->nullable();
            $table->decimal('amount', 14, 2)->nullable();
            $table->string('status')->nullable();
            $table->integer('paid')->default(0);
            $table->string('receipt')->nullable();
            $table->date('discount_date')->nullable()->comment('Fecha en que fue descontado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advances');
    }
};
