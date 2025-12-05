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
        // Add indexes only if they don't already exist (from previous migration)
        Schema::table('signals', function (Blueprint $table) {
            if (!$this->indexExists('signals', 'signals_is_published_published_date_index')) {
                $table->index(['is_published', 'published_date'], 'signals_is_published_published_date_index');
            }
        });

        Schema::table('plan_subscriptions', function (Blueprint $table) {
            if (!$this->indexExists('plan_subscriptions', 'plan_subscriptions_user_id_is_current_plan_expired_at_index')) {
                $table->index(['user_id', 'is_current', 'plan_expired_at'], 'plan_subscriptions_user_id_is_current_plan_expired_at_index');
            }
        });

        Schema::table('user_signals', function (Blueprint $table) {
            if (!$this->indexExists('user_signals', 'user_signals_user_id_signal_id_unique')) {
                $table->unique(['user_id', 'signal_id'], 'user_signals_user_id_signal_id_unique');
            }
        });

        Schema::table('dashboard_signals', function (Blueprint $table) {
            if (!$this->indexExists('dashboard_signals', 'dashboard_signals_user_id_signal_id_unique')) {
                $table->unique(['user_id', 'signal_id'], 'dashboard_signals_user_id_signal_id_unique');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!$this->indexExists('payments', 'payments_user_id_status_index')) {
                $table->index(['user_id', 'status'], 'payments_user_id_status_index');
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
        Schema::table('signals', function (Blueprint $table) {
            $table->dropIndex('signals_is_published_published_date_index');
        });

        Schema::table('plan_subscriptions', function (Blueprint $table) {
            $table->dropIndex('plan_subscriptions_user_id_is_current_plan_expired_at_index');
        });

        Schema::table('user_signals', function (Blueprint $table) {
            $table->dropUnique('user_signals_user_id_signal_id_unique');
        });

        Schema::table('dashboard_signals', function (Blueprint $table) {
            $table->dropUnique('dashboard_signals_user_id_signal_id_unique');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_user_id_status_index');
        });
    }

    /**
     * Check if index exists
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        $result = $connection->select(
            "SELECT COUNT(*) as count FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, $table, $index]
        );
        return $result[0]->count > 0;
    }
};
