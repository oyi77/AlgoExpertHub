<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('configurations', function (Blueprint $table) {
            if (!Schema::hasColumn('configurations', 'dark_logo')) {
                $table->string('dark_logo')->nullable()->after('logo');
            }
            if (!Schema::hasColumn('configurations', 'trade_charge')) {
                $table->decimal('trade_charge', 28, 8)->default(0)->nullable()->after('transfer_max_amount');
            }
            if (!Schema::hasColumn('configurations', 'min_trade_balance')) {
                $table->decimal('min_trade_balance', 28, 8)->default(0)->nullable()->after('trade_charge');
            }
            if (!Schema::hasColumn('configurations', 'trade_limit')) {
                $table->decimal('trade_limit', 28, 8)->default(0)->nullable()->after('min_trade_balance');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('configurations', function (Blueprint $table) {
            if (Schema::hasColumn('configurations', 'dark_logo')) {
                $table->dropColumn('dark_logo');
            }
            if (Schema::hasColumn('configurations', 'trade_charge')) {
                $table->dropColumn('trade_charge');
            }
            if (Schema::hasColumn('configurations', 'min_trade_balance')) {
                $table->dropColumn('min_trade_balance');
            }
            if (Schema::hasColumn('configurations', 'trade_limit')) {
                $table->dropColumn('trade_limit');
            }
        });
    }
};

