<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Console\Command;

class FindDuplicateSections extends Command
{
    protected $signature = 'sections:find-duplicates';
    protected $description = 'Find and display duplicate sections on pages';

    public function handle()
    {
        $homePage = Page::where('slug', 'home')->orWhere('name', 'Home')->first();
        
        if (!$homePage) {
            $this->error('Home page not found');
            return;
        }

        $this->info("Home page ID: {$homePage->id}, Slug: {$homePage->slug}");
        
        $sections = $homePage->widgets;
        $this->info("Total sections: {$sections->count()}");
        
        // Group by section name
        $grouped = $sections->groupBy('sections');
        
        $this->info("\nSections grouped by name:");
        foreach ($grouped as $sectionName => $items) {
            $count = $items->count();
            if ($count > 1) {
                $this->error("DUPLICATE: {$sectionName} appears {$count} times");
                $this->line("  IDs: " . $items->pluck('id')->implode(', '));
            } else {
                $this->line("  {$sectionName}: 1 time (ID: {$items->first()->id})");
            }
        }
        
        // Check for sections with same name but different hashes (formatting differences)
        $this->info("\nChecking for sections with same name but different formatting:");
        $sectionNames = $sections->pluck('sections')->unique();
        foreach ($sectionNames as $name) {
            $sameName = $sections->where('sections', $name);
            if ($sameName->count() > 1) {
                $hashes = $sameName->pluck('sections_hash')->unique();
                if ($hashes->count() > 1) {
                    $this->warn("  {$name} has same name but different hashes (formatting differences)");
                    foreach ($sameName as $s) {
                        $this->line("    ID: {$s->id}, Hash: {$s->sections_hash}");
                    }
                }
            }
        }
    }
}
