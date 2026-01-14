<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDemoBalanceToUsersTable extends Migration
{
    /**
     * Add demo_balance column to users table for paper trading
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('demo_balance', 28, 8)->default(10000.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('demo_balance');
        });
    }
}
