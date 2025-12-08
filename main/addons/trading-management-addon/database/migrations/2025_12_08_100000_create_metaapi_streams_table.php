<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create metaapi_streams table
 * 
 * Tracks active MetaAPI streaming connections per account/symbol/timeframe
 */
class CreateMetaapiStreamsTable extends Migration
{
    public function up()
    {
        Schema::create('metaapi_streams', function (Blueprint $table) {
            $table->id();
            $table->string('account_id', 100)->comment('MetaAPI account ID');
            $table->string('symbol', 50)->comment('Trading symbol (e.g., EURUSD, BTC/USDT)');
            $table->string('timeframe', 10)->comment('Timeframe (e.g., M1, H1, D1)');
            $table->enum('status', ['active', 'paused', 'error'])->default('active');
            $table->integer('subscriber_count')->default(0)->comment('Number of bots/connections using this stream');
            $table->timestamp('last_update_at')->nullable()->comment('Last time stream data was updated');
            $table->text('last_error')->nullable()->comment('Last error message if status is error');
            $table->timestamps();

            // Unique constraint: one stream per account/symbol/timeframe
            $table->unique(['account_id', 'symbol', 'timeframe'], 'unique_stream');
            
            // Indexes
            $table->index('account_id');
            $table->index('status');
            $table->index(['symbol', 'timeframe']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('metaapi_streams');
    }
}
