<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('pagebuilder_templates')) {
            Schema::create('pagebuilder_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->longText('content')->nullable(); // JSON content
                $table->string('preview_image')->nullable();
                $table->string('category')->nullable();
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('pagebuilder_templates');
    }
};
