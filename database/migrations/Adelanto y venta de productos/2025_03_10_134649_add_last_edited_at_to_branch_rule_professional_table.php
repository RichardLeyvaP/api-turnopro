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
        Schema::table('branch_rule_professional', function (Blueprint $table) {
            // Agregar el campo last_edited_at
            $table->timestamp('last_edited_at')->nullable()->useCurrent()->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_rule_professional', function (Blueprint $table) {
            $table->dropColumn('last_edited_at');
        });
    }
};
