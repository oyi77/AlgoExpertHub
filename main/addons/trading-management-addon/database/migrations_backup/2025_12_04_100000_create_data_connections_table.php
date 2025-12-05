<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create data_connections table
 * 
 * For storing data provider connections (mtapi.io, CCXT, custom APIs)
 * Separate from execution_connections (trade execution)
 */
class CreateDataConnectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('data_connections', function (Blueprint $table) {
            $table->id();
            
            // Ownership
            $table->unsignedBigInteger('user_id')->nullable()->comment('For user-owned connections');
            $table->unsignedBigInteger('admin_id')->nullable()->comment('For admin-owned connections');
            
            // Connection details
            $table->string('name')->comment('Connection name');
            $table->enum('type', ['mtapi', 'ccxt_crypto', 'custom_api'])->default('mtapi')
                ->comment('Provider type');
            $table->string('provider')->comment('Provider identifier (e.g., binance, mt4_account_123)');
            $table->text('credentials')->comment('Encrypted API keys, tokens, etc.');
            $table->json('config')->nullable()->comment('Provider-specific config');
            
            // Status
            $table->enum('status', ['active', 'inactive', 'error', 'testing'])->default('inactive');
            $table->boolean('is_active')->default(false);
            $table->boolean('is_admin_owned')->default(false);
            
            // Health monitoring
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->text('last_error')->nullable();
            
            // Settings
            $table->json('settings')->nullable()->comment('Data preferences (symbols, timeframes)');
            
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');

            // Indexes
            $table->index('user_id');
            $table->index('admin_id');
            $table->index('type');
            $table->index('status');
            $table->index('is_active');
            $table->index('is_admin_owned');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('data_connections');
    }
}

