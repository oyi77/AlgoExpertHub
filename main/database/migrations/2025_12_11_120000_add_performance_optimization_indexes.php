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
        // Add additional indexes for frequently queried columns
        Schema::table('signals', function (Blueprint $table) {
            // Index for currency_pair_id, time_frame_id, market_id combination queries
            if (!$this->indexExists('signals', 'signals_currency_pair_time_frame_market_index')) {
                $table->index(['currency_pair_id', 'time_frame_id', 'market_id'], 'signals_currency_pair_time_frame_market_index');
            }
            
            // Index for auto_created and channel_source_id queries
            if (!$this->indexExists('signals', 'signals_auto_created_channel_source_index')) {
                $table->index(['auto_created', 'channel_source_id'], 'signals_auto_created_channel_source_index');
            }
            
            // Index for direction queries
            if (!$this->indexExists('signals', 'signals_direction_index')) {
                $table->index('direction');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            // Index for status and email verification queries
            if (!$this->indexExists('users', 'users_status_is_email_verified_index')) {
                $table->index(['status', 'is_email_verified'], 'users_status_is_email_verified_index');
            }
            
            // Index for referral queries
            if (!$this->indexExists('users', 'users_ref_id_index')) {
                $table->index('ref_id');
            }
            
            // Index for KYC status queries
            if (!$this->indexExists('users', 'users_kyc_status_index')) {
                $table->index('kyc_status');
            }
        });

        Schema::table('plan_subscriptions', function (Blueprint $table) {
            // Index for plan_id and status queries
            if (!$this->indexExists('plan_subscriptions', 'plan_subscriptions_plan_id_status_index')) {
                $table->index(['plan_id', 'status'], 'plan_subscriptions_plan_id_status_index');
            }
            
            // Index for expiry date queries
            if (!$this->indexExists('plan_subscriptions', 'plan_subscriptions_end_date_index')) {
                $table->index('end_date');
            }
        });

        Schema::table('plan_signals', function (Blueprint $table) {
            // Index for signal_id queries (reverse lookup)
            if (!$this->indexExists('plan_signals', 'plan_signals_signal_id_index')) {
                $table->index('signal_id');
            }
        });

        Schema::table('dashboard_signals', function (Blueprint $table) {
            // Index for created_at for ordering
            if (!$this->indexExists('dashboard_signals', 'dashboard_signals_created_at_index')) {
                $table->index('created_at');
            }
        });

        Schema::table('user_signals', function (Blueprint $table) {
            // Index for created_at for ordering
            if (!$this->indexExists('user_signals', 'user_signals_created_at_index')) {
                $table->index('created_at');
            }
        });

        Schema::table('transactions', function (Blueprint $table) {
            // Index for type and status queries
            if (!$this->indexExists('transactions', 'transactions_type_status_index')) {
                $table->index(['type', 'status'], 'transactions_type_status_index');
            }
        });

        Schema::table('deposits', function (Blueprint $table) {
            // Index for gateway_id and status
            if (!$this->indexExists('deposits', 'deposits_gateway_id_status_index')) {
                $table->index(['gateway_id', 'status'], 'deposits_gateway_id_status_index');
            }
        });

        Schema::table('withdraws', function (Blueprint $table) {
            // Index for status and created_at
            if (!$this->indexExists('withdraws', 'withdraws_status_created_at_index')) {
                $table->index(['status', 'created_at'], 'withdraws_status_created_at_index');
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
            $table->dropIndex('signals_currency_pair_time_frame_market_index');
            $table->dropIndex('signals_auto_created_channel_source_index');
            $table->dropIndex('signals_direction_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_status_is_email_verified_index');
            $table->dropIndex('users_ref_id_index');
            $table->dropIndex('users_kyc_status_index');
        });

        Schema::table('plan_subscriptions', function (Blueprint $table) {
            $table->dropIndex('plan_subscriptions_plan_id_status_index');
            $table->dropIndex('plan_subscriptions_end_date_index');
        });

        Schema::table('plan_signals', function (Blueprint $table) {
            $table->dropIndex('plan_signals_signal_id_index');
        });

        Schema::table('dashboard_signals', function (Blueprint $table) {
            $table->dropIndex('dashboard_signals_created_at_index');
        });

        Schema::table('user_signals', function (Blueprint $table) {
            $table->dropIndex('user_signals_created_at_index');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_type_status_index');
        });

        Schema::table('deposits', function (Blueprint $table) {
            $table->dropIndex('deposits_gateway_id_status_index');
        });

        Schema::table('withdraws', function (Blueprint $table) {
            $table->dropIndex('withdraws_status_created_at_index');
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