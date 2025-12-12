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
        Schema::create('ai_decisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('signal_id')->nullable(); // Optional link if analyzing an existing signal
            $table->string('symbol'); // EURUSD, BTC/USDT
            $table->string('timeframe'); // H1, D1
            $table->enum('action', ['BUY', 'SELL', 'HOLD', 'NEUTRAL'])->default('NEUTRAL');
            $table->tinyInteger('confidence')->unsigned()->default(0); // 0-100
            $table->text('reasoning')->nullable();
            $table->text('prompt_used')->nullable(); // For debugging/audit
            $table->json('analysis_data')->nullable(); // Structured data extracted (entry, sl, tp)
            $table->unsignedBigInteger('ai_connection_id')->nullable(); // Which AI was used
            $table->string('model_used')->nullable(); // gpt-4, gemini-pro
            $table->timestamps();

            $table->index(['symbol', 'timeframe']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ai_decisions');
    }
};
