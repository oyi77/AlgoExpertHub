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
        Schema::create('pagebuilder_widgets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon')->nullable(); // Icon class or image path
            $table->string('category')->default('general'); // general, form, media, social, etc.
            $table->json('config')->nullable(); // Widget configuration schema
            $table->text('html_template')->nullable(); // HTML template
            $table->text('css_template')->nullable(); // CSS template
            $table->text('js_template')->nullable(); // JavaScript template
            $table->json('default_settings')->nullable(); // Default widget settings
            $table->boolean('is_active')->default(true);
            $table->boolean('is_pro')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->index(['category', 'is_active']);
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagebuilder_widgets');
    }
};
