<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Drop the old unique constraint if it exists
        // This allows multiple iteratable items with the same name
        $indexExists = DB::select("SHOW INDEX FROM sp_contents WHERE Key_name = 'contents_unique_key'");
        if (!empty($indexExists)) {
            Schema::table('contents', function (Blueprint $table) {
                $table->dropUnique('contents_unique_key');
            });
        }

        // Note: We removed the unique constraint to allow multiple iteratable items
        // Uniqueness for non_iteratable items is now handled in the ContentSeeder
        // using updateOrCreate logic
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Re-add the unique constraint if needed (not recommended as it breaks iteratable content)
        $indexExists = DB::select("SHOW INDEX FROM sp_contents WHERE Key_name = 'contents_unique_key'");
        if (empty($indexExists)) {
            Schema::table('contents', function (Blueprint $table) {
                $table->unique(['type', 'name', 'theme', 'language_id'], 'contents_unique_key');
            });
        }
    }
};
