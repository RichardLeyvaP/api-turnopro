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
        Schema::create('finances', function (Blueprint $table) {
            $table->id();
            $table->date('data');
            $table->string('operation');
            $table->integer('control');
            $table->decimal('amount', 8, 2);
            $table->string('comment');
            $table->string('file');

            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('expense_id')->nullable();
            $table->unsignedBigInteger('revenue_id')->nullable();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('expense_id')->references('id')->on('expenses')->onDelete('set null');
            $table->foreign('revenue_id')->references('id')->on('revenues')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finances');
    }
};
