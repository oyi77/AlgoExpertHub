<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Optimize deposits queries: where('status', 1)->where('user_id', ...)
        if (!Schema::hasIndex('deposits', 'deposits_user_id_status_index')) {
            Schema::table('deposits', function (Blueprint $table) {
                $table->index(['user_id', 'status'], 'deposits_user_id_status_index');
            });
        }

        // Optimize withdraws queries: where('status', 1)->where('user_id', ...)
        if (!Schema::hasIndex('withdraws', 'withdraws_user_id_status_index')) {
            Schema::table('withdraws', function (Blueprint $table) {
                $table->index(['user_id', 'status'], 'withdraws_user_id_status_index');
            });
        }

        // Optimize transactions queries: $user->transactions()->latest() => where('user_id', ...)->orderBy('created_at', 'desc')
        if (!Schema::hasIndex('transactions', 'transactions_user_id_created_at_index')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index(['user_id', 'created_at'], 'transactions_user_id_created_at_index');
            });
        }

        // Optimize dashboard_signals queries: where('user_id', $user->id)->latest()
        if (!Schema::hasIndex('dashboard_signals', 'dashboard_signals_user_id_created_at_index')) {
            Schema::table('dashboard_signals', function (Blueprint $table) {
                $table->index(['user_id', 'created_at'], 'dashboard_signals_user_id_created_at_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropIndex('deposits_user_id_status_index');
        });

        Schema::table('withdraws', function (Blueprint $table) {
            $table->dropIndex('withdraws_user_id_status_index');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_user_id_created_at_index');
        });

        Schema::table('dashboard_signals', function (Blueprint $table) {
            $table->dropIndex('dashboard_signals_user_id_created_at_index');
        });
    }
};
