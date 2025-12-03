<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_connection_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_id')->constrained('ai_connections')->onDelete('cascade');
            $table->string('feature'); // e.g., 'translation', 'parsing', 'market_analysis'
            $table->integer('tokens_used')->default(0);
            $table->decimal('cost', 10, 6)->default(0.000000); // Cost in USD
            $table->boolean('success')->default(true);
            $table->integer('response_time_ms')->nullable(); // Response time in milliseconds
            $table->text('error_message')->nullable();
            $table->timestamp('created_at'); // Only created_at, no updated_at for logs

            // Indexes for analytics
            $table->index(['connection_id', 'created_at']);
            $table->index(['feature', 'created_at']);
            $table->index(['success', 'created_at']);
            $table->index('created_at'); // For time-based queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_connection_usage');
    }
};

