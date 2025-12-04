<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create data_connection_logs table
 * 
 * For logging connection activities (connect, fetch, errors)
 */
class CreateDataConnectionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('data_connection_logs', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('data_connection_id');
            
            // Action details
            $table->enum('action', ['connect', 'disconnect', 'fetch_data', 'test', 'error'])
                ->comment('Action performed');
            $table->enum('status', ['success', 'failed'])->default('success');
            
            // Message and metadata
            $table->text('message')->nullable();
            $table->json('metadata')->nullable()->comment('Extra info (rows fetched, latency, etc.)');
            
            $table->timestamp('created_at')->useCurrent();

            // Foreign key
            $table->foreign('data_connection_id')->references('id')->on('data_connections')
                ->onDelete('cascade');

            // Indexes
            $table->index('data_connection_id');
            $table->index(['data_connection_id', 'created_at'], 'connection_time_idx');
            $table->index('action');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('data_connection_logs');
    }
}

