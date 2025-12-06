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
        Schema::create('srm_model_versions', function (Blueprint $table) {
            $table->id();
            $table->enum('model_type', ['slippage_prediction', 'performance_score', 'risk_optimization'])->comment('Type of ML model');
            $table->string('version', 50)->comment('Model version identifier');
            $table->enum('status', ['training', 'active', 'deprecated', 'testing'])->default('training')->comment('Model status');
            
            // Model Parameters (JSON)
            $table->json('parameters')->nullable()->comment('Model hyperparameters, weights, etc.');
            $table->unsignedInteger('training_data_count')->default(0)->comment('Number of training samples');
            $table->timestamp('training_date_start')->nullable()->comment('Training start date');
            $table->timestamp('training_date_end')->nullable()->comment('Training end date');
            
            // Performance Metrics
            $table->decimal('accuracy', 5, 2)->nullable()->comment('Overall accuracy percentage');
            $table->decimal('mse', 10, 6)->nullable()->comment('Mean Squared Error (for regression)');
            $table->decimal('r2_score', 5, 4)->nullable()->comment('R² score (for regression)');
            
            // Validation Metrics
            $table->decimal('validation_accuracy', 5, 2)->nullable()->comment('Validation accuracy');
            $table->decimal('validation_mse', 10, 6)->nullable()->comment('Validation MSE');
            
            // Deployment
            $table->timestamp('deployed_at')->nullable()->comment('When model was deployed');
            $table->timestamp('deprecated_at')->nullable()->comment('When model was deprecated');
            
            // Metadata
            $table->text('notes')->nullable()->comment('Additional notes');
            $table->timestamps();
            
            // Indexes
            $table->index(['model_type', 'status'], 'idx_model_type');
            $table->index('version', 'idx_version');
            $table->unique(['model_type', 'version'], 'uk_model_version');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('srm_model_versions');
    }
};

