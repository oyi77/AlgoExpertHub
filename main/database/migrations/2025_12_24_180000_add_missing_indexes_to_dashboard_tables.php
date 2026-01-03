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
        // Transactions table - used in dashboard for recent transactions: $user->transactions()->latest()->limit(3)
        Schema::table('transactions', function (Blueprint $table) {
            if (!$this->indexExists('transactions', 'transactions_user_id_created_at_index')) {
                $table->index(['user_id', 'created_at'], 'transactions_user_id_created_at_index');
            }
        });

        // Dashboard Signals - used in dashboard with latest(): DashboardSignal::where('user_id', $user->id)->latest()
        Schema::table('dashboard_signals', function (Blueprint $table) {
            if (!$this->indexExists('dashboard_signals', 'dashboard_signals_user_id_created_at_index')) {
                $table->index(['user_id', 'created_at'], 'dashboard_signals_user_id_created_at_index');
            }
        });

        // Deposits - used for total deposit calculation with status check: $user->deposits()->where('status', 1)->sum('amount')
        Schema::table('deposits', function (Blueprint $table) {
            if (!$this->indexExists('deposits', 'deposits_user_id_status_index')) {
                $table->index(['user_id', 'status'], 'deposits_user_id_status_index');
            }
        });

        // Withdraws - used for total withdraw calculation with status check: $user->withdraws()->where('status', 1)->sum('withdraw_amount')
        Schema::table('withdraws', function (Blueprint $table) {
            if (!$this->indexExists('withdraws', 'withdraws_user_id_status_index')) {
                $table->index(['user_id', 'status'], 'withdraws_user_id_status_index');
            }
        });

        // Tickets - used for counting support tickets: $user->tickets()->count()
        Schema::table('tickets', function (Blueprint $table) {
            if (!$this->indexExists('tickets', 'tickets_user_id_index')) {
                $table->index(['user_id'], 'tickets_user_id_index');
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
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_user_id_created_at_index');
        });

        Schema::table('dashboard_signals', function (Blueprint $table) {
            $table->dropIndex('dashboard_signals_user_id_created_at_index');
        });

        Schema::table('deposits', function (Blueprint $table) {
            $table->dropIndex('deposits_user_id_status_index');
        });

        Schema::table('withdraws', function (Blueprint $table) {
            $table->dropIndex('withdraws_user_id_status_index');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_user_id_index');
        });
    }

    /**
     * Check if index exists to prevent errors
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        try {
            $result = $connection->select(
                "SELECT COUNT(*) as count FROM information_schema.statistics
                 WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                [$databaseName, $table, $index]
            );
            return ($result[0]->count ?? 0) > 0;
        } catch (\Exception $e) {
            // Fallback for non-MySQL or permissions issues, though project uses MySQL.
            return false;
        }
    }
};
