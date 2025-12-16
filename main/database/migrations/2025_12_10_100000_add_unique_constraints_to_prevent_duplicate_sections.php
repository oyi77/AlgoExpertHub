<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddUniqueConstraintsToPreventDuplicateSections extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Clean up duplicate contents first
        // Keep the newest record (MAX id) and delete older duplicates
        $duplicates = DB::table('contents')
            ->select('type', 'name', 'theme', 'language_id', DB::raw('MAX(id) as max_id'))
            ->groupBy('type', 'name', 'theme', 'language_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('contents')
                ->where('type', $duplicate->type)
                ->where('name', $duplicate->name)
                ->where('theme', $duplicate->theme)
                ->where('language_id', $duplicate->language_id)
                ->where('id', '!=', $duplicate->max_id)
                ->delete();
        }

        // Add unique constraint to contents table (if it doesn't exist)
        $indexExists = DB::select("SHOW INDEX FROM sp_contents WHERE Key_name = 'contents_unique_key'");
        if (empty($indexExists)) {
            Schema::table('contents', function (Blueprint $table) {
                $table->unique(['type', 'name', 'theme', 'language_id'], 'contents_unique_key');
            });
        }

        // Clean up duplicate page_sections first
        // Keep the newest record (MAX id) and delete older duplicates
        $pageSectionDuplicates = DB::table('page_sections')
            ->select('page_id', 'sections', DB::raw('MAX(id) as max_id'))
            ->groupBy('page_id', 'sections')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($pageSectionDuplicates as $duplicate) {
            DB::table('page_sections')
                ->where('page_id', $duplicate->page_id)
                ->where('sections', $duplicate->sections)
                ->where('id', '!=', $duplicate->max_id)
                ->delete();
        }

        // Add hash column for sections to enable unique constraint
        if (!Schema::hasColumn('page_sections', 'sections_hash')) {
            Schema::table('page_sections', function (Blueprint $table) {
                $table->string('sections_hash', 64)->nullable()->after('sections');
            });
        }

        // Populate hash for existing records
        DB::table('page_sections')
            ->whereNull('sections_hash')
            ->get()
            ->each(function ($record) {
                DB::table('page_sections')
                    ->where('id', $record->id)
                    ->update(['sections_hash' => hash('sha256', $record->sections)]);
            });

        // Make hash not nullable
        if (Schema::hasColumn('page_sections', 'sections_hash')) {
            try {
                DB::statement('ALTER TABLE sp_page_sections MODIFY sections_hash VARCHAR(64) NOT NULL');
            } catch (\Exception $e) {
                // Already not null, ignore
            }
        }

        // Add unique constraint if it doesn't exist
        $pageSectionIndexExists = DB::select("SHOW INDEX FROM sp_page_sections WHERE Key_name = 'page_sections_unique_key'");
        if (empty($pageSectionIndexExists) && Schema::hasColumn('page_sections', 'sections_hash')) {
            Schema::table('page_sections', function (Blueprint $table) {
                $table->unique(['page_id', 'sections_hash'], 'page_sections_unique_key');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropUnique('contents_unique_key');
        });

        Schema::table('page_sections', function (Blueprint $table) {
            $table->dropUnique('page_sections_unique_key');
            if (Schema::hasColumn('page_sections', 'sections_hash')) {
                $table->dropColumn('sections_hash');
            }
        });
    }
}
