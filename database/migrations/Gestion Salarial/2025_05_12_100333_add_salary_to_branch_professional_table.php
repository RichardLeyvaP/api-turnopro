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
        Schema::table('branch_professional', function (Blueprint $table) {
            $table->decimal('salary', 14, 2)->nullable()->comment('Salario del profesional en la sucursal');
            $table->integer('tier1_min_sales')->nullable(0)->comment('Minimum sales for Tier 1 commission');
            $table->decimal('tier1_commission_rate', 5, 2)->default(0)->comment('Commission rate for Tier 1 (5-29 sales)');

            $table->integer('tier2_min_sales')->default(0)->comment('Minimum sales for Tier 2 commission');
            $table->decimal('tier2_commission_rate', 5, 2)->default(0)->comment('Commission rate for Tier 2 (30-99 sales)');
   
            $table->integer('tier3_min_sales')->default(0)->comment('Minimum sales for Tier 3 commission');
            $table->decimal('tier3_commission_rate', 5, 2)->default(0)->comment('Commission rate for Tier 3 (100+ sales)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_professional', function (Blueprint $table) {
            $table->dropColumn([
                'salary',
                'tier1_min_sales',
                'tier1_commission_rate',
                'tier2_min_sales',
                'tier2_commission_rate',
                'tier3_min_sales',
                'tier3_commission_rate'
            ]);
        });
    }
};
