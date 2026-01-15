<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('execution_connections')) {
            return;
        }

        Schema::table('execution_connections', function (Blueprint $table) {
            if (!Schema::hasColumn('execution_connections', 'consecutive_failures')) {
                $table->integer('consecutive_failures')->default(0)->after('max_consecutive_failures');
            }

            if (!Schema::hasColumn('execution_connections', 'last_failure_at')) {
                $table->dateTime('last_failure_at')->nullable()->after('consecutive_failures');
            }

            if (!$this->indexExists('execution_connections', 'execution_connections_consecutive_failures_index')) {
                $table->index('consecutive_failures');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('execution_connections')) {
            return;
        }

        Schema::table('execution_connections', function (Blueprint $table) {
            if ($this->indexExists('execution_connections', 'execution_connections_consecutive_failures_index')) {
                $table->dropIndex('execution_connections_consecutive_failures_index');
            }

            if (Schema::hasColumn('execution_connections', 'last_failure_at')) {
                $table->dropColumn('last_failure_at');
            }

            if (Schema::hasColumn('execution_connections', 'consecutive_failures')) {
                $table->dropColumn('consecutive_failures');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        $result = $connection->select(
            "SELECT COUNT(*) as count FROM information_schema.statistics\n             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, $table, $index]
        );

        return (int) $result[0]->count > 0;
    }
};
