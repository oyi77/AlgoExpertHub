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
        Schema::table('execution_analytics', function (Blueprint $table) {
            $table->decimal('sharpe_ratio', 10, 4)->nullable()->after('additional_metrics')->comment('Risk-adjusted return');
            $table->decimal('expectancy', 20, 8)->nullable()->after('sharpe_ratio')->comment('Average expected value per trade');
            $table->decimal('sortino_ratio', 10, 4)->nullable()->after('expectancy')->comment('Risk-adjusted return (downside deviation)');
            $table->decimal('mae', 20, 8)->nullable()->after('sortino_ratio')->comment('Maximum Adverse Excursion');
            $table->decimal('mfe', 20, 8)->nullable()->after('mae')->comment('Maximum Favorable Excursion');
            $table->decimal('recovery_factor', 10, 4)->nullable()->after('mfe')->comment('Recovery factor');
            $table->decimal('calmar_ratio', 10, 4)->nullable()->after('recovery_factor')->comment('Calmar ratio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('execution_analytics', function (Blueprint $table) {
            $table->dropColumn([
                'sharpe_ratio',
                'expectancy',
                'sortino_ratio',
                'mae',
                'mfe',
                'recovery_factor',
                'calmar_ratio',
            ]);
        });
    }
};

