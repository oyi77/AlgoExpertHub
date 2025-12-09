<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create expert_advisors table
 * 
 * Stores MT4/MT5 Expert Advisor files and configurations
 */
class CreateExpertAdvisorsTable extends Migration
{
    public function up()
    {
        Schema::create('expert_advisors', function (Blueprint $table) {
            $table->id();
            
            // Ownership
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->boolean('is_admin_owned')->default(false);
            
            // EA Identity
            $table->string('name');
            $table->text('description')->nullable();
            
            // EA File
            $table->enum('ea_type', ['mt4', 'mt5'])->comment('MT4 or MT5 Expert Advisor');
            $table->string('ea_file_path')->nullable()->comment('Path to .ex4 or .ex5 file');
            $table->string('original_filename')->nullable();
            $table->unsignedInteger('file_size')->nullable()->comment('File size in bytes');
            
            // EA Configuration
            $table->json('parameters')->nullable()->comment('EA input parameters (JSON)');
            $table->json('default_parameters')->nullable()->comment('Default parameter values');
            
            // Status
            $table->enum('status', ['active', 'inactive', 'testing'])->default('active');
            $table->boolean('is_template')->default(false);
            $table->enum('visibility', ['private', 'public', 'admin_only'])->default('private');
            $table->boolean('clonable')->default(false);
            
            // Metadata
            $table->string('version')->nullable();
            $table->text('author')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
            
            // Indexes
            $table->index('user_id');
            $table->index('admin_id');
            $table->index('ea_type');
            $table->index('status');
            $table->index('is_template');
            $table->index('visibility');
        });
    }

    public function down()
    {
        Schema::dropIfExists('expert_advisors');
    }
}
