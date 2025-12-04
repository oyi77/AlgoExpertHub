<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add trading_bot_id to execution_logs
 * Links executions to trading bots for tracking
 */
class AddTradingBotIdToExecutionLogs extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('execution_logs')) {
            return;
        }

        Schema::table('execution_logs', function (Blueprint $table) {
            // Check which column name exists (connection_id or execution_connection_id)
            $afterColumn = Schema::hasColumn('execution_logs', 'execution_connection_id') 
                ? 'execution_connection_id' 
                : (Schema::hasColumn('execution_logs', 'connection_id') ? 'connection_id' : null);
            
            if (!Schema::hasColumn('execution_logs', 'trading_bot_id')) {
                if ($afterColumn) {
                    $table->unsignedBigInteger('trading_bot_id')->nullable()->after($afterColumn);
                } else {
                    $table->unsignedBigInteger('trading_bot_id')->nullable();
                }
                
                if (Schema::hasTable('trading_bots')) {
                    $table->foreign('trading_bot_id')->references('id')->on('trading_bots')->onDelete('set null');
                }
                $table->index('trading_bot_id');
            }
        });
    }

    public function down()
    {
        Schema::table('execution_logs', function (Blueprint $table) {
            $table->dropForeign(['trading_bot_id']);
            $table->dropIndex(['trading_bot_id']);
            $table->dropColumn('trading_bot_id');
        });
    }
}
