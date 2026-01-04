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
        // Add a composite index on user_id and created_at to optimize transaction history queries.
        // This speeds up fetching the latest transactions for a specific user,
        // which is a frequent operation on the user dashboard.
        // Query: $user->transactions()->latest()->limit(3)->get();
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'transactions_user_id_created_at_index');
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
    }
};
