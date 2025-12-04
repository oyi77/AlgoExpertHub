<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create execution_connections table
 * 
 * Migrated from trading-execution-engine-addon
 * NOTE: Now separated from data_connections (execution ONLY, not data fetching)
 * 
 * Links to:
 * - data_connections (for market data)
 * - trading_presets (for risk management)
 */
class CreateExecutionConnectionsTable extends Migration
{
    public function up()
    {
        Schema::create('execution_connections', function (Blueprint $table) {
            $table->id();
            
            // Ownership
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            
            // Connection details
            $table->string('name');
            $table->enum('type', ['crypto', 'fx'])->default('crypto');
            $table->string('exchange_name')->comment('Exchange/broker identifier');
            $table->text('credentials')->comment('Encrypted API keys for EXECUTION');
            
            // Status
            $table->enum('status', ['active', 'inactive', 'error', 'testing'])->default('inactive');
            $table->boolean('is_active')->default(false);
            $table->boolean('is_admin_owned')->default(false);
            
            // Health monitoring
            $table->text('last_error')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            
            // Settings
            $table->json('settings')->nullable();
            
            // Integration: Trading Preset (risk management)
            $table->unsignedBigInteger('preset_id')->nullable()->comment('Link to trading_presets');
            
            // NEW: Link to DataConnection (for market data fetching)
            $table->unsignedBigInteger('data_connection_id')->nullable()
                ->comment('Optional: Link to data_connection for market data');
            
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
            $table->foreign('preset_id')->references('id')->on('trading_presets')->onDelete('set null');
            $table->foreign('data_connection_id')->references('id')->on('data_connections')->onDelete('set null');

            // Indexes
            $table->index('user_id');
            $table->index('admin_id');
            $table->index('type');
            $table->index('status');
            $table->index('is_active');
            $table->index('preset_id');
            $table->index('data_connection_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('execution_connections');
    }
}

