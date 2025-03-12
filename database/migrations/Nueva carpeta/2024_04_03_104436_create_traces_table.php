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
        Schema::create('traces', function (Blueprint $table) {
            $table->id();
            $table->string('branch')->nullabe();
            $table->string('cashier')->nullabe();
            $table->string('client')->nullabe();
            $table->string('data')->nullabe();
            $table->string('operation')->nullabe();
            $table->string('details')->nullabe();
            $table->decimal('amount', 14, 2)->nullable();
            $table->string('description')->nullabe();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traces');
    }
};
