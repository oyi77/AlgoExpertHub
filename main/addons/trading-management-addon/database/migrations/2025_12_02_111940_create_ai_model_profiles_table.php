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
        Schema::create('ai_model_profiles', function (Blueprint $table) {
            $table->id();
            
            // Identity
            $table->string('name');
            $table->text('description')->nullable();
            
            // Owner & Visibility
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->enum('visibility', ['PRIVATE', 'PUBLIC_MARKETPLACE'])->default('PRIVATE');
            $table->boolean('clonable')->default(true);
            $table->boolean('enabled')->default(true);
            
            // Provider & Model
            $table->string('provider'); // openai, gemini, claude, local, etc.
            $table->string('model_name'); // e.g., gpt-4, gemini-pro
            $table->string('api_key_ref')->nullable(); // Reference to config/env, not plain text
            
            // Behavior
            $table->enum('mode', ['CONFIRM', 'SCAN', 'POSITION_MGMT'])->default('CONFIRM');
            $table->text('prompt_template')->nullable(); // Template with placeholders
            $table->json('settings')->nullable(); // temperature, max_tokens, etc.
            
            // Limits (optional)
            $table->integer('max_calls_per_minute')->nullable();
            $table->integer('max_calls_per_day')->nullable();
            
            // Meta
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('created_by_user_id');
            $table->index('visibility');
            $table->index('enabled');
            $table->index('provider');
            $table->index('mode');
            
            // Foreign Keys
            $table->foreign('created_by_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
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
};
