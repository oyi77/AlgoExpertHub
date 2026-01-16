<?php

declare(strict_types=1);

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
        Schema::table('execution_logs', function (Blueprint $table) {
            // Add ai_decision_id column as nullable foreign key
            $table->foreignId('ai_decision_id')
                  ->nullable()
                  ->constrained('ai_decisions')
                  ->onDelete('set null')
                  ->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('execution_logs', function (Blueprint $table) {
            // Drop foreign key constraint first, then column
            $table->dropForeign(['ai_decision_id']);
            $table->dropColumn('ai_decision_id');
        });
    }
};
