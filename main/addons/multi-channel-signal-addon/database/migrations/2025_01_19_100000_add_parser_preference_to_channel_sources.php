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
        Schema::table('channel_sources', function (Blueprint $table) {
            $table->enum('parser_preference', ['auto', 'pattern', 'ai'])->default('auto')->after('auto_publish_confidence_threshold')
                ->comment('auto = try pattern first, then AI; pattern = only pattern templates; ai = only AI parsing');
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

