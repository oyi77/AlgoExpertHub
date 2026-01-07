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
        // Add index to dashboard_signals for user-scoped latest queries
        // Used in UserDashboardService: DashboardSignal::where('user_id', ...)->latest()
        if (Schema::hasTable('dashboard_signals')) {
            try {
                Schema::table('dashboard_signals', function (Blueprint $table) {
                    $table->index(['user_id', 'created_at'], 'dashboard_signals_user_id_created_at_index');
                });
            } catch (\Exception $e) {
                // Index likely already exists
            }
        }

        // Add index to user_signals for user-scoped time-based grouping
        // Used in UserDashboardService: UserSignal::where('user_id', ...)->groupBy(MONTHNAME(created_at))
        if (Schema::hasTable('user_signals')) {
            try {
                Schema::table('user_signals', function (Blueprint $table) {
                    $table->index(['user_id', 'created_at'], 'user_signals_user_id_created_at_index');
                });
            } catch (\Exception $e) {
                // Index likely already exists
            }
        }

        // Add index to transactions for user-scoped latest queries
        // Used in UserDashboardService: $user->transactions()->latest()
        if (Schema::hasTable('transactions')) {
            try {
                Schema::table('transactions', function (Blueprint $table) {
                    $table->index(['user_id', 'created_at'], 'transactions_user_id_created_at_index');
                });
            } catch (\Exception $e) {
                // Index likely already exists
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('dashboard_signals')) {
            try {
                Schema::table('dashboard_signals', function (Blueprint $table) {
                    $table->dropIndex('dashboard_signals_user_id_created_at_index');
                });
            } catch (\Exception $e) {
                // Index likely does not exist
            }
        }

        if (Schema::hasTable('user_signals')) {
            try {
                Schema::table('user_signals', function (Blueprint $table) {
                    $table->dropIndex('user_signals_user_id_created_at_index');
                });
            } catch (\Exception $e) {
                // Index likely does not exist
            }
        }

        if (Schema::hasTable('transactions')) {
            try {
                Schema::table('transactions', function (Blueprint $table) {
                    $table->dropIndex('transactions_user_id_created_at_index');
                });
            } catch (\Exception $e) {
                // Index likely does not exist
            }
        }
    }
};
