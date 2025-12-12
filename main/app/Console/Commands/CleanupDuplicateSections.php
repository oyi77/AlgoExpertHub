<?php

namespace App\Console\Commands;

use App\Models\Content;
use App\Models\PageSection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupDuplicateSections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sections:cleanup-duplicates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up duplicate sections in contents and page_sections tables';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Cleaning up duplicate sections...');

        // Clean up duplicate contents
        // Keep the newest record (MAX id) and delete older duplicates
        $duplicates = DB::table('contents')
            ->select('type', 'name', 'theme', 'language_id', DB::raw('MAX(id) as max_id'))
            ->groupBy('type', 'name', 'theme', 'language_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $contentsDeleted = 0;
        foreach ($duplicates as $duplicate) {
            $deleted = DB::table('contents')
                ->where('type', $duplicate->type)
                ->where('name', $duplicate->name)
                ->where('theme', $duplicate->theme)
                ->where('language_id', $duplicate->language_id)
                ->where('id', '!=', $duplicate->max_id)
                ->delete();
            $contentsDeleted += $deleted;
        }

        $this->info("Cleaned up {$contentsDeleted} duplicate contents.");

        // Clean up duplicate page sections
        // Keep the newest record (MAX id) and delete older duplicates
        $pageSectionDuplicates = DB::table('page_sections')
            ->select('page_id', 'sections', DB::raw('MAX(id) as max_id'))
            ->groupBy('page_id', 'sections')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $pageSectionsDeleted = 0;
        foreach ($pageSectionDuplicates as $duplicate) {
            $deleted = DB::table('page_sections')
                ->where('page_id', $duplicate->page_id)
                ->where('sections', $duplicate->sections)
                ->where('id', '!=', $duplicate->max_id)
                ->delete();
            $pageSectionsDeleted += $deleted;
        }

        $this->info("Cleaned up {$pageSectionsDeleted} duplicate page sections.");

        // Count remaining duplicates for verification
        $contentsCount = Content::select('type', 'name', 'theme', 'language_id')
            ->groupBy('type', 'name', 'theme', 'language_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        $pageSectionsCount = PageSection::select('page_id', 'sections')
            ->groupBy('page_id', 'sections')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($contentsCount > 0) {
            $this->warn("Warning: {$contentsCount} duplicate content groups still exist.");
        } else {
            $this->info('✓ No duplicate contents found.');
        }

        if ($pageSectionsCount > 0) {
            $this->warn("Warning: {$pageSectionsCount} duplicate page section groups still exist.");
        } else {
            $this->info('✓ No duplicate page sections found.');
        }

        $this->info('Cleanup completed!');

        return Command::SUCCESS;
    }
}
