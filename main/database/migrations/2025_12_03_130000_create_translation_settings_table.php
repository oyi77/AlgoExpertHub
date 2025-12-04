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
        // Skip if table already exists
        if (Schema::hasTable('translation_settings')) {
            return;
        }

        Schema::create('translation_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ai_connection_id'); // Primary AI connection for translations
            $table->unsignedBigInteger('fallback_connection_id')->nullable(); // Fallback if primary fails
            $table->integer('batch_size')->default(10); // Number of keys to translate per batch
            $table->integer('delay_between_requests_ms')->default(100); // Delay between API calls
            $table->json('settings')->nullable(); // Additional settings (temperature, max_tokens overrides)
            $table->timestamps();

            // Foreign keys to AI Connection Addon (only if table exists)
            if (Schema::hasTable('ai_connections')) {
                $table->foreign('ai_connection_id')
                      ->references('id')
                      ->on('ai_connections')
                      ->onDelete('restrict');

                $table->foreign('fallback_connection_id')
                      ->references('id')
                      ->on('ai_connections')
                      ->onDelete('set null');
            }

            // Indexes
            $table->index('ai_connection_id');
        });

        // Insert default settings if an AI connection exists
        if (Schema::hasTable('ai_connections')) {
            $defaultConnection = DB::table('ai_connections')->first();
            if ($defaultConnection) {
                DB::table('translation_settings')->insert([
                    'ai_connection_id' => $defaultConnection->id,
                    'batch_size' => 10,
                    'delay_between_requests_ms' => 100,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translation_settings');
    }
};

