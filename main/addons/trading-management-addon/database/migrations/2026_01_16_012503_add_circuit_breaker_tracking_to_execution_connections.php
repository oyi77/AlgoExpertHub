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
        Schema::table('execution_connections', function (Blueprint $table) {
            $table->integer('consecutive_failures')->default(0)->after('max_consecutive_failures');
            $table->timestamp('last_failure_at')->nullable()->after('consecutive_failures');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('execution_connections', function (Blueprint $table) {
            $table->dropColumn(['consecutive_failures', 'last_failure_at']);
        });
    }
};
