<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSourceTypeToMarketDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('market_data')) {
            return;
        }

        Schema::table('market_data', function (Blueprint $table) {
            if (!Schema::hasColumn('market_data', 'source_type')) {
                $table->string('source_type', 50)->default('unknown')->after('volume')->comment('mtapi, ccxt, etc.');
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
        if (!Schema::hasTable('market_data')) {
            return;
        }

        Schema::table('market_data', function (Blueprint $table) {
            if (Schema::hasColumn('market_data', 'source_type')) {
                $table->dropColumn('source_type');
            }
        });
    }
}
