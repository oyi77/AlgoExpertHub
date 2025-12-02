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
        Schema::create('ai_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->unique(); // openai, gemini, etc.
            $table->string('name'); // Display name
            $table->text('api_key')->nullable(); // Encrypted API key
            $table->string('api_url')->nullable(); // API endpoint URL
            $table->string('model')->nullable(); // Model name (e.g., gpt-3.5-turbo, gemini-pro)
            $table->json('settings')->nullable(); // Additional provider-specific settings
            $table->boolean('enabled')->default(false);
            $table->integer('priority')->default(50); // Parser priority
            $table->integer('timeout')->default(30); // Request timeout in seconds
            $table->float('temperature', 3, 2)->default(0.3); // AI temperature
            $table->integer('max_tokens')->default(500); // Max response tokens
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_configurations');
    }
};

