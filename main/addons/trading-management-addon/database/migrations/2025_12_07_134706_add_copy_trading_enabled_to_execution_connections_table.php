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
        Schema::table('execution_connections', function (Blueprint $table) {
            $table->boolean('copy_trading_enabled')->default(false)->after('trade_execution_enabled')->comment('Enable copy trading for this connection');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('execution_connections', function (Blueprint $table) {
            $table->dropColumn('copy_trading_enabled');
        });
    }
};
