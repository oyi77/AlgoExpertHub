<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add enhancement fields to trading_bot_execution_logs table
 * 
 * Adds filter_results, execution_time_ms, and error_details
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('trading_bot_execution_logs', function (Blueprint $table) {
            // Filter results (JSON)
            if (!Schema::hasColumn('trading_bot_execution_logs', 'filter_results')) {
                $table->json('filter_results')->nullable()->after('notes')
                    ->comment('Filter evaluation results for this execution');
            }

            // Execution time in milliseconds
            if (!Schema::hasColumn('trading_bot_execution_logs', 'execution_time_ms')) {
                $table->integer('execution_time_ms')->nullable()->after('filter_results')
                    ->comment('Execution time in milliseconds');
            }

            // Error details
            if (!Schema::hasColumn('trading_bot_execution_logs', 'error_details')) {
                $table->text('error_details')->nullable()->after('execution_time_ms')
                    ->comment('Detailed error information if execution failed');
            }
        });
    }

    public function down()
    {
        Schema::table('trading_bot_execution_logs', function (Blueprint $table) {
            if (Schema::hasColumn('trading_bot_execution_logs', 'error_details')) {
                $table->dropColumn('error_details');
            }

            if (Schema::hasColumn('trading_bot_execution_logs', 'execution_time_ms')) {
                $table->dropColumn('execution_time_ms');
            }

            if (Schema::hasColumn('trading_bot_execution_logs', 'filter_results')) {
                $table->dropColumn('filter_results');
            }
        });
    }
};

