<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('internal_trades')) {
            return;
        }

        Schema::table('internal_trades', function (Blueprint $table) {
            if (!Schema::hasColumn('internal_trades', 'is_paper')) {
                $table->boolean('is_paper')->default(false)->after('status');
            }

            if (!$this->indexExists('internal_trades', 'internal_trades_is_paper_index')) {
                $table->index('is_paper');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('internal_trades')) {
            return;
        }

        Schema::table('internal_trades', function (Blueprint $table) {
            if ($this->indexExists('internal_trades', 'internal_trades_is_paper_index')) {
                $table->dropIndex('internal_trades_is_paper_index');
            }

            if (Schema::hasColumn('internal_trades', 'is_paper')) {
                $table->dropColumn('is_paper');
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
