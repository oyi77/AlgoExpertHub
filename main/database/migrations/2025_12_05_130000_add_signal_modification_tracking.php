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
        Schema::table('signals', function (Blueprint $table) {
            if (!Schema::hasColumn('signals', 'last_modified_at')) {
                $table->timestamp('last_modified_at')->nullable()->after('published_date');
            }
            if (!Schema::hasColumn('signals', 'modification_count')) {
                $table->integer('modification_count')->default(0)->after('last_modified_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signals', function (Blueprint $table) {
            if (Schema::hasColumn('signals', 'last_modified_at')) {
                $table->dropColumn('last_modified_at');
            }
            if (Schema::hasColumn('signals', 'modification_count')) {
                $table->dropColumn('modification_count');
            }
        });
    }
};
