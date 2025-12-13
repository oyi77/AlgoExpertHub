<?php

namespace Database\Seeders;

use App\Models\Content;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Seeder;

class TradingLandingThemeSeeder extends Seeder
{
    public function run()
    {
        $theme = 'trading-landing';
        
        // Create non-iteratable content for each widget
        $contents = [
            [
                'type' => 'non_iteratable',
                'name' => 'hero',
                'content' => [
                    'badge_text' => '1M+ Users Active',
                    'title' => 'Master the Markets, Maximize Your Profits',
                    'description' => 'Trade smarter with real-time insights, powerful tools, and expert strategies at your fingertips.',
                    'button_text' => 'Explore Now',
                    'button_text_link' => route('user.register'),
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'non_iteratable',
                'name' => 'market-trends',
                'content' => [
                    'title' => 'Real-Time Market Trends',
                    'description' => 'Stay Ahead with Up-to-the-Second Market Data on Major Currency Pairs and Cryptocurrencies',
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'non_iteratable',
                'name' => 'how_works',
                'content' => [
                    'section_header' => 'How It Works',
                    'title' => 'Start Trading in 4 Simple Steps',
                    'color_text_for_title' => 'Get started with AlgoExpertHub and begin your automated trading journey in minutes',
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'non_iteratable',
                'name' => 'benefits',
                'content' => [
                    'section_header' => 'Why Choose Us',
                    'title' => 'Powerful Benefits for Every Trader',
                    'color_text_for_title' => 'Discover what makes AlgoExpertHub the preferred choice for traders worldwide',
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'non_iteratable',
                'name' => 'trading-demo',
                'content' => [
                    'title' => 'Try Demo Trading',
                    'description' => 'Experience our trading platform with virtual money. No risk, no commitment.',
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'non_iteratable',
                'name' => 'trading-instruments',
                'content' => [
                    'title' => 'Explore Global Market Opportunities',
                    'description' => 'Diversified Trading Instruments Across Major Asset Classes',
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'non_iteratable',
                'name' => 'why-choose-us',
                'content' => [
                    'section_header' => 'Built for Traders',
                    'title' => 'Built for Traders, Backed by Experts',
                    'color_text_for_title' => 'Discover the tools, insights, and support that set us apart in global markets',
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'non_iteratable',
                'name' => 'testimonial',
                'content' => [
                    'section_header' => 'Testimonials',
                    'title' => 'Trusted by Thousands of Traders Worldwide',
                    'color_text_for_title' => 'See what our community says about their trading experience with AlgoExpertHub',
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'non_iteratable',
                'name' => 'blog',
                'content' => [
                    'section_header' => 'Latest News',
                    'title' => 'Trading Insights & Market Analysis',
                    'color_text_for_title' => 'Stay informed with expert analysis and trading tips',
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'non_iteratable',
                'name' => 'team',
                'content' => [
                    'section_header' => 'Our Team',
                    'title' => 'Meet the Experts Behind Your Success',
                    'color_text_for_title' => 'Our experienced team of traders and analysts is here to support you',
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'non_iteratable',
                'name' => 'cta-education',
                'content' => [
                    'title' => 'Sharpen Your Trading Edge with Our Education Content',
                    'description' => 'Master the markets with our comprehensive trading education, from beginner basics to advanced strategies.',
                    'button_text' => 'Start Learning',
                    'button_text_link' => route('user.register'),
                    'button_two_text' => 'Try Free Demo',
                    'button_two_text_link' => '#trading-demo',
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'non_iteratable',
                'name' => 'account-types',
                'content' => [
                    'title' => 'Compare Our Account Types',
                    'description' => 'Choose the Right Trading Account for Your Strategy and Risk Appetite',
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'non_iteratable',
                'name' => 'footer-cta',
                'content' => [
                    'title' => 'Open Your Account in Minutes with Our Simple Registration Process',
                    'subtitle' => 'Join over 10,000+ traders worldwide',
                    'button_text' => 'Start Trading Now',
                    'button_text_link' => route('user.register'),
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
        ];

        // Create or update content records
        foreach ($contents as $contentData) {
            Content::updateOrCreate(
                [
                    'name' => $contentData['name'],
                    'theme' => $theme,
                    'type' => $contentData['type'],
                    'language_id' => 0
                ],
                $contentData
            );
        }

        // Create or update home page for trading-landing theme
        $page = Page::updateOrCreate(
            ['slug' => 'home'],
            [
                'name' => 'Home',
                'slug' => 'home',
                'order' => 1,
                'is_dropdown' => 0,
                'seo_keywords' => ['trading', 'signals', 'forex', 'crypto', 'algo trading'],
                'seo_description' => 'AlgoExpertHub - Your premier destination for algorithmic trading signals across Forex, Crypto, and Stock markets.',
                'status' => 1,
            ]
        );

        // Define sections for trading-landing home page (matching widget file names)
        $sections = [
            'hero',
            'market-trends',
            'how_works',
            'benefits',
            'trading-demo',
            'trading-instruments',
            'why-choose-us',
            'testimonial',
            'blog',
            'team',
            'cta-education',
            'account-types',
            'footer-cta'
        ];

        // Use updateOrCreate to avoid duplicates - only update sections if they don't exist
        // This prevents overwriting existing sections for other themes
        foreach ($sections as $index => $section) {
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
        
        // Note: This will add trading-landing sections to the existing home page
        // without deleting other sections. Since PageSection has no theme column,
        // all themes share the same page structure, but content is theme-specific.

        $this->command->info('Trading-Landing theme content and home page seeded successfully!');
    }
}

