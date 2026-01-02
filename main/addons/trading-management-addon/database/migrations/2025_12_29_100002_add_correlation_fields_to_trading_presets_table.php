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
        Schema::table('trading_presets', function (Blueprint $table) {
            $table->decimal('max_correlation_exposure_pct', 5, 2)->default(50.0)->after('max_positions_per_symbol')->comment('Max % of equity in correlated pairs');
            $table->decimal('correlation_threshold', 5, 2)->default(0.7)->after('max_correlation_exposure_pct')->comment('Correlation coefficient threshold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trading_presets', function (Blueprint $table) {
            $table->dropColumn([
                'max_correlation_exposure_pct',
                'correlation_threshold',
            ]);
        });
    }
};

