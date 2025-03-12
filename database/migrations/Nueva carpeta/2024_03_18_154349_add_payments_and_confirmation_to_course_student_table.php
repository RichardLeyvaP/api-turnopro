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
        Schema::table('course_student', function (Blueprint $table) {
            $table->decimal('reservation_payment', 8, 2)->default(0.00);
            $table->decimal('total_payment', 8, 2)->default(0.00);
            $table->boolean('enrollment_confirmed')->default(false); // O ->default(0) si prefieres ser explícito con el valor booleano
            $table->string('image_url')->default('');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_student', function (Blueprint $table) {
            //
        });
    }
};
