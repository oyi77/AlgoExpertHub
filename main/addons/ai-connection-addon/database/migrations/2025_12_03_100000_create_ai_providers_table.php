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
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Display name (e.g., "OpenAI", "Google Gemini")
            $table->string('slug')->unique(); // Identifier (e.g., "openai", "gemini", "openrouter")
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedBigInteger('default_connection_id')->nullable(); // FK to ai_connections (set after connections exist)
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_providers');
    }
};

