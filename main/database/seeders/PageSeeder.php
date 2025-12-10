<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run()
    {
        $pages = [
            [
                'name' => 'Home',
                'slug' => 'home',
                'order' => 1,
                'is_dropdown' => 0,
                'seo_keywords' => ['trading', 'signals', 'forex', 'crypto', 'algo trading'],
                'seo_description' => 'AlgoExpertHub - Your premier destination for algorithmic trading signals across Forex, Crypto, and Stock markets.',
                'status' => 1,
                'sections' => ['banner', 'about', 'benefits', 'how_works', 'plans', 'trade', 'referral', 'team', 'testimonial', 'blog']
            ],
            [
                'name' => 'About',
                'slug' => 'about',
                'order' => 2,
                'is_dropdown' => 0,
                'seo_keywords' => ['about us', 'company', 'trading platform'],
                'seo_description' => 'Learn about AlgoExpertHub and our mission to democratize algorithmic trading.',
                'status' => 1,
                'sections' => ['about', 'overview', 'how_works', 'team']
            ],
            [
                'name' => 'Packages',
                'slug' => 'packages',
                'order' => 3,
                'is_dropdown' => 0,
                'seo_keywords' => ['plans', 'pricing', 'subscription'],
                'seo_description' => 'Choose the perfect plan for your trading journey. Flexible monthly and yearly subscriptions.',
                'status' => 1,
                'sections' => ['plans']
            ],
            [
                'name' => 'Contact',
                'slug' => 'contact',
                'order' => 4,
                'is_dropdown' => 0,
                'seo_keywords' => ['contact', 'support', 'help'],
                'seo_description' => 'Get in touch with our support team. We\'re here to help you succeed.',
                'status' => 1,
                'sections' => ['contact']
            ],
            [
                'name' => 'Blog',
                'slug' => 'blog',
                'order' => 5,
                'is_dropdown' => 1,
                'seo_keywords' => ['blog', 'news', 'trading tips'],
                'seo_description' => 'Latest trading news, tips, and market analysis from our experts.',
                'status' => 1,
                'sections' => ['blog']
            ]
        ];

        foreach ($pages as $pageData) {
            $sections = $pageData['sections'];
            unset($pageData['sections']);

            $page = Page::firstOrCreate(
                ['slug' => $pageData['slug']],
                $pageData
            );

            // Create page sections (prevent duplicates) with proper order
            foreach ($sections as $index => $section) {
                // Calculate hash for uniqueness check
                $sectionsHash = hash('sha256', $section);
                
                PageSection::updateOrCreate(
                    [
                        'page_id' => $page->id,
                        'sections_hash' => $sectionsHash
                    ],
                    [
                        'sections' => $section,
                        'order' => $index + 1
                    ]
                );
            }
        }
    }
}

