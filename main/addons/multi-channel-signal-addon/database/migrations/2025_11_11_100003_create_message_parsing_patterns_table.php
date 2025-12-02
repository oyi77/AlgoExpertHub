<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessageParsingPatternsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_parsing_patterns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_source_id')->nullable()->comment('NULL for global patterns');
            $table->unsignedBigInteger('user_id')->nullable()->comment('NULL for admin-created global patterns');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('pattern_type', ['regex', 'template', 'ai_fallback'])->default('regex');
            $table->json('pattern_config')->comment('Pattern definitions, field mappings, regex rules');
            $table->integer('priority')->default(0)->comment('Higher priority tried first');
            $table->boolean('is_active')->default(1);
            $table->integer('success_count')->default(0)->comment('Number of successful parses');
            $table->integer('failure_count')->default(0)->comment('Number of failed parses');
            $table->timestamps();

            $table->foreign('channel_source_id')->references('id')->on('channel_sources')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->index('channel_source_id');
            $table->index('user_id');
            $table->index('priority');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('message_parsing_patterns');
    }
}

