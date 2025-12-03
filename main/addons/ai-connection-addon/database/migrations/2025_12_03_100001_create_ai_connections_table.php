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
        Schema::create('ai_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('ai_providers')->onDelete('cascade');
            $table->string('name'); // e.g., "OpenAI Production", "OpenAI Backup"
            $table->text('credentials'); // Encrypted JSON: {api_key, base_url, etc.}
            $table->json('settings')->nullable(); // {model, temperature, timeout, max_tokens, etc.}
            $table->enum('status', ['active', 'inactive', 'error'])->default('active');
            $table->integer('priority')->default(50); // 1=primary, 2=secondary, etc. Lower = higher priority
            $table->integer('rate_limit_per_minute')->nullable(); // Requests per minute limit
            $table->integer('rate_limit_per_day')->nullable(); // Requests per day limit
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->integer('error_count')->default(0);
            $table->integer('success_count')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['provider_id', 'status', 'priority']);
            $table->index('status');
            $table->index('priority');
            $table->index('last_used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_connections');
    }
};

