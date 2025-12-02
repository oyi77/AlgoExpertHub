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
        Schema::create('openrouter_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('api_key'); // Encrypted
            $table->string('model_id');
            $table->string('site_url')->nullable();
            $table->string('site_name')->nullable();
            $table->decimal('temperature', 3, 2)->default(0.3);
            $table->integer('max_tokens')->default(500);
            $table->integer('timeout')->default(30);
            $table->boolean('enabled')->default(true);
            $table->integer('priority')->default(50);
            $table->boolean('use_for_parsing')->default(false);
            $table->boolean('use_for_analysis')->default(false);
            $table->timestamps();

            $table->index(['enabled', 'use_for_parsing']);
            $table->index(['enabled', 'use_for_analysis']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('openrouter_configurations');
    }
};

