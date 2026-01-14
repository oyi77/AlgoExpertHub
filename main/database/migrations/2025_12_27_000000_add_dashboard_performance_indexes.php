<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add indexes for dashboard performance

        $this->addIndexSafe('withdraws', ['user_id', 'status'], 'withdraws_user_id_status_index');
        $this->addIndexSafe('deposits', ['user_id', 'status'], 'deposits_user_id_status_index');
        $this->addIndexSafe('transactions', ['user_id', 'created_at'], 'transactions_user_id_created_at_index');

        // These tables are large and often queried by user_id and sorted by date
        $this->addIndexSafe('user_signals', ['user_id', 'created_at'], 'user_signals_user_id_created_at_index');
        $this->addIndexSafe('dashboard_signals', ['user_id', 'created_at'], 'dashboard_signals_user_id_created_at_index');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('withdraws', function (Blueprint $table) {
            $table->dropIndex('withdraws_user_id_status_index');
        });

        Schema::table('deposits', function (Blueprint $table) {
            $table->dropIndex('deposits_user_id_status_index');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_user_id_created_at_index');
        });

        Schema::table('user_signals', function (Blueprint $table) {
            $table->dropIndex('user_signals_user_id_created_at_index');
        });

        Schema::table('dashboard_signals', function (Blueprint $table) {
            $table->dropIndex('dashboard_signals_user_id_created_at_index');
        });
    }

    /**
     * Safely add an index catching duplicate key errors
     */
    private function addIndexSafe(string $table, array $columns, string $indexName)
    {
        try {
            Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        } catch (QueryException $e) {
            // Check if error is due to duplicate key/index (Code 1061 for MySQL is duplicate key name)
            // Or generic logic: if it fails, we assume it's likely because it exists or similar conflict.
            // We can check error code if we want to be strict, but for idempotency usually ignoring is enough
            // if the goal is "ensure it exists".
            if (str_contains($e->getMessage(), 'Duplicate key name') || str_contains($e->getMessage(), 'already exists')) {
                return;
            }
            throw $e;
        }
    }
};
