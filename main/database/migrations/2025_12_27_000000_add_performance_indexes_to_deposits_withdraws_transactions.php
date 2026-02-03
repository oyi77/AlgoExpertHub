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
        // ⚡ Bolt Optimization: Add composite indexes to speed up dashboard aggregation queries.
        // Queries in UserDashboardService filter by user_id, status, and created_at (range).
        // Without these indexes, every dashboard load performs full table scans on these tables.
        // Expected Impact: Reduces query time from O(N) to O(log N) for filtering and sorting operations.

        // Note: Using try-catch for idempotency as per project conventions to handle potential duplicate index errors during re-runs.
        try {
            Schema::table('deposits', function (Blueprint $table) {
                // Optimization for: Deposit::where('status', 1)->where('user_id', ...)->where('created_at', '>=', ...)
                $table->index(['user_id', 'status', 'created_at'], 'deposits_user_id_status_created_at_index');
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Index likely already exists
        }

        try {
            Schema::table('withdraws', function (Blueprint $table) {
                // Optimization for: Withdraw::where('status', 1)->where('user_id', ...)->where('created_at', '>=', ...)
                $table->index(['user_id', 'status', 'created_at'], 'withdraws_user_id_status_created_at_index');
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Index likely already exists
        }

        try {
            Schema::table('transactions', function (Blueprint $table) {
                // Optimization for: $user->transactions()->latest()->limit(3)
                // Default `latest()` uses `created_at DESC`.
                $table->index(['user_id', 'created_at'], 'transactions_user_id_created_at_index');
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Index likely already exists
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        try {
            Schema::table('deposits', function (Blueprint $table) {
                $table->dropIndex('deposits_user_id_status_created_at_index');
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Index likely doesn't exist
        }

        try {
            Schema::table('withdraws', function (Blueprint $table) {
                $table->dropIndex('withdraws_user_id_status_created_at_index');
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Index likely doesn't exist
        }

        try {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropIndex('transactions_user_id_created_at_index');
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Index likely doesn't exist
        }
    }
};
