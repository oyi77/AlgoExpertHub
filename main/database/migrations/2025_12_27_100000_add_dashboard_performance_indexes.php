<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Optimize: $user->deposits()->where('status', 1)->sum('amount')
        // Optimize: Deposit::where('status', 1)->where('user_id', ...)->groupBy('month')
        if (Schema::hasTable('deposits')) {
            try {
                Schema::table('deposits', function (Blueprint $table) {
                    $table->index(['user_id', 'status'], 'deposits_user_id_status_index');
                });
            } catch (QueryException $e) {
                // Ignore "Duplicate key name" error (Code 1061 for MySQL)
                // We re-throw if it's not an index existence issue to avoid hiding other errors
                if (!$this->isDuplicateIndexError($e)) {
                    throw $e;
                }
            }
        }

        // Optimize: $user->withdraws()->where('status', 1)->sum('withdraw_amount')
        if (Schema::hasTable('withdraws')) {
            try {
                Schema::table('withdraws', function (Blueprint $table) {
                    $table->index(['user_id', 'status'], 'withdraws_user_id_status_index');
                });
            } catch (QueryException $e) {
                if (!$this->isDuplicateIndexError($e)) {
                    throw $e;
                }
            }
        }

        // Optimize: DashboardSignal::where('user_id', $user->id)->latest()
        if (Schema::hasTable('dashboard_signals')) {
            try {
                Schema::table('dashboard_signals', function (Blueprint $table) {
                    $table->index(['user_id', 'created_at'], 'dashboard_signals_user_id_created_at_index');
                });
            } catch (QueryException $e) {
                if (!$this->isDuplicateIndexError($e)) {
                    throw $e;
                }
            }
        }

        // Optimize: UserSignal::where('user_id', auth()->id())->...->groupBy('month')
        if (Schema::hasTable('user_signals')) {
            try {
                Schema::table('user_signals', function (Blueprint $table) {
                    $table->index(['user_id', 'created_at'], 'user_signals_user_id_created_at_index');
                });
            } catch (QueryException $e) {
                if (!$this->isDuplicateIndexError($e)) {
                    throw $e;
                }
            }
        }

        // Optimize: $user->transactions()->latest()->limit(3)
        if (Schema::hasTable('transactions')) {
            try {
                Schema::table('transactions', function (Blueprint $table) {
                    $table->index(['user_id', 'created_at'], 'transactions_user_id_created_at_index');
                });
            } catch (QueryException $e) {
                if (!$this->isDuplicateIndexError($e)) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Check if the exception is due to duplicate index.
     */
    protected function isDuplicateIndexError(QueryException $e): bool
    {
        $message = $e->getMessage();
        // MySQL: 1061 Duplicate key name
        // PostgreSQL: 42710 relation "..." already exists
        // SQLite: index "..." already exists
        return str_contains($message, 'Duplicate key name') ||
               str_contains($message, 'already exists');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'deposits' => 'deposits_user_id_status_index',
            'withdraws' => 'withdraws_user_id_status_index',
            'dashboard_signals' => 'dashboard_signals_user_id_created_at_index',
            'user_signals' => 'user_signals_user_id_created_at_index',
            'transactions' => 'transactions_user_id_created_at_index',
        ];

        foreach ($tables as $table => $index) {
            if (Schema::hasTable($table)) {
                try {
                    Schema::table($table, function (Blueprint $table) use ($index) {
                        $table->dropIndex($index);
                    });
                } catch (\Exception $e) {
                    // Ignore if index doesn't exist
                }
            }
        }
    }
};
