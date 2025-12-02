<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExecutionConnectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('execution_connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('For user-owned connections');
            $table->unsignedBigInteger('admin_id')->nullable()->comment('For admin-owned connections');
            $table->string('name');
            $table->enum('type', ['crypto', 'fx'])->default('crypto');
            $table->string('exchange_name')->comment('Exchange/broker identifier (e.g., binance, mt4_account_123)');
            $table->text('credentials')->comment('Encrypted API keys, tokens, etc.');
            $table->enum('status', ['active', 'inactive', 'error', 'testing'])->default('inactive');
            $table->boolean('is_active')->default(false);
            $table->boolean('is_admin_owned')->default(false);
            $table->text('last_error')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->json('settings')->nullable()->comment('Position sizing, risk limits, etc.');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');

            $table->index('user_id');
            $table->index('admin_id');
            $table->index('type');
            $table->index('status');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('execution_connections');
    }
}

