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
        Schema::create('srm_predictions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('execution_log_id')->nullable()->comment('FK to execution_logs');
            $table->unsignedBigInteger('signal_id')->nullable()->comment('FK to signals');
            $table->unsignedBigInteger('connection_id')->nullable()->comment('FK to execution_connections');
            
            // Prediction Type
            $table->enum('prediction_type', ['slippage', 'performance_score', 'lot_optimization'])->comment('Type of prediction');
            
            // Input Features
            $table->string('symbol', 50)->nullable()->comment('Trading symbol');
            $table->enum('trading_session', ['TOKYO', 'LONDON', 'NEW_YORK', 'ASIAN', 'OVERLAP'])->nullable()->comment('Trading session');
            $table->tinyInteger('day_of_week')->nullable()->comment('Day of week 1-7');
            $table->decimal('market_atr', 10, 4)->nullable()->comment('Market ATR value');
            $table->decimal('volatility_index', 8, 4)->nullable()->comment('Volatility index');
            $table->string('signal_provider_id', 255)->nullable()->comment('Signal provider identifier');
            
            // Prediction Output
            $table->decimal('predicted_value', 10, 4)->comment('Predicted value (slippage, score, or lot)');
            $table->decimal('confidence_score', 5, 2)->default(0.00)->comment('Confidence score 0-100');
            
            // Actual Result (for accuracy tracking)
            $table->decimal('actual_value', 10, 4)->nullable()->comment('Actual value after execution');
            $table->decimal('accuracy', 5, 2)->nullable()->comment('Prediction accuracy percentage');
            
            // Model Info
            $table->string('model_version', 50)->nullable()->comment('ML model version used');
            $table->string('model_type', 50)->nullable()->comment('Model type (regression, weighted_scoring, etc.)');
            
            // Metadata
            $table->timestamps();
            
            // Indexes
            $table->index('execution_log_id', 'idx_execution_log');
            $table->index('signal_id', 'idx_signal');
            $table->index('connection_id', 'idx_connection');
            $table->index('prediction_type', 'idx_prediction_type');
            $table->index('created_at', 'idx_created_at');
            
            // Foreign Keys (only if tables exist)
            if (Schema::hasTable('execution_logs')) {
                $table->foreign('execution_log_id')
                    ->references('id')
                    ->on('execution_logs')
                    ->onDelete('set null');
            }
            
            if (Schema::hasTable('signals')) {
                $table->foreign('signal_id')
                    ->references('id')
                    ->on('signals')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('srm_predictions');
    }
};

