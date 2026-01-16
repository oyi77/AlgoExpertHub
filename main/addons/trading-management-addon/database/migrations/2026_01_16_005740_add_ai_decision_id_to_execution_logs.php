<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sp_execution_logs') || !Schema::hasTable('sp_ai_decisions')) {
            return;
        }

        Schema::table('sp_execution_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('sp_execution_logs', 'ai_decision_id')) {
                $table->foreignId('ai_decision_id')
                    ->nullable()
                    ->after('connection_id')
                    ->constrained('sp_ai_decisions')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('sp_execution_logs')) {
            return;
        }

        Schema::table('sp_execution_logs', function (Blueprint $table) {
            if (Schema::hasColumn('sp_execution_logs', 'ai_decision_id')) {
                $table->dropForeign(['ai_decision_id']);
                $table->dropColumn('ai_decision_id');
            }
        });
    }
};
