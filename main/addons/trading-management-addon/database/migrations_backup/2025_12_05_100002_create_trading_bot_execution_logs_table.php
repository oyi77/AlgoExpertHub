<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create trading_bot_execution_logs table
 * 
 * Tracks all lifecycle actions (start, stop, pause, resume) for audit trail
 */
class CreateTradingBotExecutionLogsTable extends Migration
{
    public function up()
    {
        Schema::create('trading_bot_execution_logs', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('bot_id')->comment('FK to trading_bots');
            $table->enum('action', ['start', 'stop', 'pause', 'resume'])->comment('Action performed');
            $table->timestamp('executed_at')->comment('When action was executed');
            
            // Who executed it
            $table->unsignedBigInteger('executed_by_user_id')->nullable()->comment('FK to users (if user executed)');
            $table->unsignedBigInteger('executed_by_admin_id')->nullable()->comment('FK to admins (if admin executed)');
            
            $table->text('notes')->nullable()->comment('Additional notes or reason');
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('bot_id')->references('id')->on('trading_bots')->onDelete('cascade');
            if (Schema::hasTable('users')) {
                $table->foreign('executed_by_user_id')->references('id')->on('users')->onDelete('set null');
            }
            if (Schema::hasTable('admins')) {
                $table->foreign('executed_by_admin_id')->references('id')->on('admins')->onDelete('set null');
            }
            
            // Indexes
            $table->index('bot_id');
            $table->index('action');
            $table->index('executed_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('trading_bot_execution_logs');
    }
}
