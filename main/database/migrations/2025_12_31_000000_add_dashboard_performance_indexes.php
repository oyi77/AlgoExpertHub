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
        // Add composite index to transactions for latest() queries
        Schema::table('transactions', function (Blueprint $table) {
            // Check if index exists to prevent duplicate key errors
            try {
                if (!$this->indexExists('transactions', 'transactions_user_id_created_at_index')) {
                    $table->index(['user_id', 'created_at'], 'transactions_user_id_created_at_index');
                }
            } catch (\Illuminate\Database\QueryException $e) {
                // If checking fails (e.g. SQLite), try adding blindly or ignore
            }
        });

        // Add composite index to deposits for status filtering and aggregation
        Schema::table('deposits', function (Blueprint $table) {
            try {
                if (!$this->indexExists('deposits', 'deposits_user_id_status_index')) {
                    $table->index(['user_id', 'status'], 'deposits_user_id_status_index');
                }
            } catch (\Illuminate\Database\QueryException $e) {
            }
        });

        // Add composite index to withdraws for status filtering and aggregation
        Schema::table('withdraws', function (Blueprint $table) {
            try {
                if (!$this->indexExists('withdraws', 'withdraws_user_id_status_index')) {
                    $table->index(['user_id', 'status'], 'withdraws_user_id_status_index');
                }
            } catch (\Illuminate\Database\QueryException $e) {
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

        Schema::table('deposits', function (Blueprint $table) {
            $table->dropIndex('deposits_user_id_status_index');
        });

        Schema::table('withdraws', function (Blueprint $table) {
            $table->dropIndex('withdraws_user_id_status_index');
        });
    }

    /**
     * Check if index exists using information_schema
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();

        // For SQLite (testing environments), information_schema doesn't exist.
        if ($connection->getDriverName() === 'sqlite') {
            $result = $connection->select("PRAGMA index_list($table)");
            foreach ($result as $idx) {
                if ($idx->name === $index) {
                    return true;
                }
            }
            return false;
        }

        // For MySQL/MariaDB
        $databaseName = $connection->getDatabaseName();
        $result = $connection->select(
            "SELECT COUNT(*) as count FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, $table, $index]
        );
        return $result[0]->count > 0;
    }
};
