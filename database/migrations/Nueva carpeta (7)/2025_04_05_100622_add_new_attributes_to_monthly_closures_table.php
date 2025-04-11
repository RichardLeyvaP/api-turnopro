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
        Schema::table('monthly_closures', function (Blueprint $table) {
            $table->decimal('client_utility', 15, 2)->default(0)->after('utility');
            $table->decimal('client_retention', 15, 2)->default(0)->after('client_utility');
            $table->decimal('spent', 15, 2)->default(0)->after('client_retention');
            $table->decimal('system_incomes', 15, 2)->default(0)->after('spent');
            $table->decimal('difference_incomes', 15, 2)->default(0)->after('system_incomes');
            $table->decimal('difference_utility', 15, 2)->default(0)->after('difference_incomes');
            $table->decimal('difference_retention', 15, 2)->default(0)->after('difference_utility');
            $table->decimal('difference_spent', 15, 2)->default(0)->after('difference_retention');
            $table->text('description')->nullable()->after('difference_spent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_closures', function (Blueprint $table) {
            $table->dropColumn([
                'client_utility',
                'client_retention',
                'spent',
                'system_incomes',
                'difference_incomes',
                'difference_utility',
                'difference_retention',
                'difference_spent',
                'description'
            ]);
        });
    }
};
