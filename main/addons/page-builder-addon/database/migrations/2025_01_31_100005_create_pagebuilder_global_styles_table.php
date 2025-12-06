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
        Schema::create('pagebuilder_global_styles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('css'); // css, scss, less
            $table->text('content'); // CSS/SCSS/LESS content
            $table->json('settings')->nullable(); // Additional settings (media queries, etc.)
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagebuilder_global_styles');
    }
};
