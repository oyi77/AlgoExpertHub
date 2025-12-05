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
        if (Schema::hasTable('global_configurations')) {
            return;
        }

        Schema::create('global_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('config_key')->unique();
            $table->json('config_value');
            $table->text('description')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('config_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_configurations');
    }
};
