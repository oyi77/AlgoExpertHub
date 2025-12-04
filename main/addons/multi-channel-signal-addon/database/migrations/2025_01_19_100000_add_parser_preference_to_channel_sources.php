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
        // Check if table exists before altering
        if (!Schema::hasTable('channel_sources')) {
            return; // Table will be created by create_channel_sources_table migration
        }
        
        Schema::table('channel_sources', function (Blueprint $table) {
            // Check if column already exists
            if (!Schema::hasColumn('channel_sources', 'parser_preference')) {
                $table->enum('parser_preference', ['auto', 'pattern', 'ai'])->default('auto')->after('auto_publish_confidence_threshold')
                    ->comment('auto = try pattern first, then AI; pattern = only pattern templates; ai = only AI parsing');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('channel_sources', function (Blueprint $table) {
            $table->dropColumn('parser_preference');
        });
    }
};

