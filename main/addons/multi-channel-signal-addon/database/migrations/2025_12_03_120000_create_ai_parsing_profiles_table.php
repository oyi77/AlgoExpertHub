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
        Schema::create('ai_parsing_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_source_id')->nullable(); // Nullable for global/default profiles
            $table->unsignedBigInteger('ai_connection_id'); // FK to ai_connections in AI Connection Addon
            $table->string('name'); // Profile name (e.g., "OpenAI GPT-4 for Forex Signals")
            $table->text('parsing_prompt')->nullable(); // Custom prompt template for parsing
            $table->json('settings')->nullable(); // Override settings (temperature, max_tokens, etc.)
            $table->integer('priority')->default(50); // Parser priority
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('channel_source_id');
            $table->index('ai_connection_id');
            $table->index(['enabled', 'priority']);

            // Foreign key to channel_sources
            $table->foreign('channel_source_id')
                  ->references('id')
                  ->on('channel_sources')
                  ->onDelete('cascade');

            // Foreign key to ai_connections (in AI Connection Addon)
            $table->foreign('ai_connection_id')
                  ->references('id')
                  ->on('ai_connections')
                  ->onDelete('restrict'); // Don't allow deleting connection if profiles use it
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_parsing_profiles');
    }
};

