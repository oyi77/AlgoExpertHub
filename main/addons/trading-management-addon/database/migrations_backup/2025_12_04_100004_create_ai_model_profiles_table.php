<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create ai_model_profiles table
 * 
 * Migrated from ai-trading-addon to trading-management-addon
 * For storing AI model configurations for market confirmation
 */
class CreateAiModelProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Skip if table already exists (created by ai-trading-addon migration)
        if (Schema::hasTable('ai_model_profiles')) {
            return;
        }

        Schema::create('ai_model_profiles', function (Blueprint $table) {
            $table->id();
            
            // Identity
            $table->string('name');
            $table->text('description')->nullable();
            
            // Owner & Visibility
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->enum('visibility', ['PRIVATE', 'PUBLIC_MARKETPLACE'])->default('PRIVATE');
            $table->boolean('clonable')->default(false);
            $table->boolean('enabled')->default(true);
            
            // AI Connection (NEW - uses centralized ai-connection-addon)
            $table->unsignedBigInteger('ai_connection_id')->nullable();
            
            // DEPRECATED fields (kept for backward compatibility)
            $table->string('provider')->nullable()->comment('DEPRECATED: Use ai_connection_id');
            $table->string('model_name')->nullable()->comment('DEPRECATED: Use ai_connection_id');
            $table->string('api_key_ref')->nullable()->comment('DEPRECATED: Use ai_connection_id');
            
            // AI Configuration
            $table->enum('mode', ['CONFIRM', 'SCAN', 'POSITION_MGMT'])->default('CONFIRM');
            $table->text('prompt_template');
            $table->json('settings')->nullable();
            
            // Rate Limiting
            $table->integer('max_calls_per_minute')->nullable();
            $table->integer('max_calls_per_day')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Check if ai_connections table exists (ai-connection-addon)
            if (Schema::hasTable('ai_connections')) {
                $table->foreign('ai_connection_id')->references('id')->on('ai_connections')->onDelete('set null');
            }

            // Indexes
            $table->index('created_by_user_id');
            $table->index('ai_connection_id');
            $table->index('visibility');
            $table->index('enabled');
            $table->index('provider');
            $table->index('mode');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ai_model_profiles');
    }
}

