<?php

namespace Database\Seeders;

use App\Models\Content;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run()
    {
        $theme = 'default';
        
        $contents = [
            // Banner
            [
                'type' => 'non_iteratable',
                'name' => 'banner',
                'content' => [
                    'title' => 'Automate, Copy, or Lead – Your Trading',
                    'color_text_for_title' => 'Ecosystem Awaits',
                    'button_text' => 'Get Started',
                    'button_text_link' => 'register',
                    'image_one' => '63ff272a12bab1677666090.png',
                    'image_two' => '63ff272b2c6391677666091.png',
                    'repeater' => [
                        ['repeater' => 'Let our AI engine forecast and execute across all markets'],
                        ['repeater' => 'Make smarter decisions and build a profile others pay to copy'],
                        ['repeater' => 'Discover seamless autotrading tailored to your risk, on any market']
                    ]
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            
            // About
            [
                'type' => 'non_iteratable',
                'name' => 'about',
                'content' => [
                    'title' => 'Unlock Your Edge in Algorithmic Trading',
                    'color_text_for_title' => 'AlgoExperthub',
                    'button_text' => 'Launch Your Edge',
                    'button_link' => '/register',
                    'description' => 'AlgoExperthub is your all-in-one platform to automate, analyze, and amplify your trading. Leverage AI, institutional strategies, and a mastermind community.',
                    'image_one' => '641bf4c698b931679553734.png',
                    'image_two' => '641bf4c6b2e011679553734.png',
                    'repeater' => [
                        ['repeater' => 'Your Trading Signals Are Already Obsolete. Evolve Your Edge.'],
                        ['repeater' => 'From Manual Trading to Automated Strategy Architect.'],
                        ['repeater' => 'Trade with Conviction, Backed by AI & Institutional Tools.']
                    ]
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            
            // Benefits
            [
                'type' => 'non_iteratable',
                'name' => 'benefits',
                'content' => [
                    'section_header' => 'Summary of Benefits',
                    'title' => 'Everything You Need to Fast Track Your Trading',
                    'color_text_for_title' => 'Track Your Trading',
                    'image_one' => '641bfc49e3fde1679555657.png'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            
            // How it Works
            [
                'type' => 'non_iteratable',
                'name' => 'how_works',
                'content' => [
                    'section_header' => 'How it Works',
                    'title' => 'Started Trading With Algoexperthub',
                    'color_text_for_title' => 'With Algoexperthub'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            
            // Plans
            [
                'type' => 'non_iteratable',
                'name' => 'plans',
                'content' => [
                    'section_header' => 'Packages',
                    'title' => 'Our Best Packages',
                    'color_text_for_title' => 'Packages'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            
            // Contact
            [
                'type' => 'non_iteratable',
                'name' => 'contact',
                'content' => [
                    'section_header' => 'Contact',
                    'title' => 'We\'d Love to Hear From You',
                    'color_text_for_title' => 'Hear From You',
                    'email' => 'support@algoexperthub.com',
                    'phone' => '+1 (800) 123-4567',
                    'address' => 'Visit our office HQ, Trading Center, New York',
                    'form_header' => 'Love to hear from you, Get in touch',
                    'color_text_for_form_header' => 'Get in touch'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            
            // Footer
            [
                'type' => 'non_iteratable',
                'name' => 'footer',
                'content' => [
                    'footer_short_details' => 'AlgoExpertHub - Advanced trading signals platform powered by AI and institutional-grade strategies.'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            
            // Trade Section
            [
                'type' => 'non_iteratable',
                'name' => 'trade',
                'content' => [
                    'section_header' => 'Live Trading',
                    'title' => 'Join the Algoexperthub community',
                    'color_text_for_title' => 'Algoexperthub community',
                    'button_text' => 'Start Trading',
                    'button_link' => 'register'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            
            // Why Choose Us
            [
                'type' => 'non_iteratable',
                'name' => 'why_choose_us',
                'content' => [
                    'section_header' => 'Choose Us',
                    'title' => 'Why Choose AlgoExperthub',
                    'color_text_for_title' => 'AlgoExperthub'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            
            // Referral
            [
                'type' => 'non_iteratable',
                'name' => 'referral',
                'content' => [
                    'section_header' => 'Referral',
                    'title' => 'Our Forex Trading Referral',
                    'color_text_for_title' => 'Trading Referral'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            
            // Team
            [
                'type' => 'non_iteratable',
                'name' => 'team',
                'content' => [
                    'section_header' => 'Our Team',
                    'title' => 'Our Forex Trading Specialist',
                    'color_text_for_title' => 'Forex Trading'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            
            // Testimonial
            [
                'type' => 'non_iteratable',
                'name' => 'testimonial',
                'content' => [
                    'section_header' => 'Testimonials',
                    'title' => 'What Our Customer Says',
                    'color_text_for_title' => 'Our Customer'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            
            // Blog
            [
                'type' => 'non_iteratable',
                'name' => 'blog',
                'content' => [
                    'section_header' => 'Blog Post',
                    'title' => 'Our Latest News',
                    'color_text_for_title' => 'News'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            
            // Auth
            [
                'type' => 'non_iteratable',
                'name' => 'auth',
                'content' => [
                    'title' => 'Welcome to AlgoExpertHub'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            
            // Iteratable items - Benefits
            [
                'type' => 'iteratable',
                'name' => 'benefits',
                'content' => [
                    'title' => '20+ Proven Trading Strategies',
                    'icon' => 'fab fa-searchengin',
                    'description' => 'Access a curated library of institutional-grade strategies ready to deploy or customize.',
                    'image_one' => ''
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'iteratable',
                'name' => 'benefits',
                'content' => [
                    'title' => 'VIP Insights & Direct Support',
                    'icon' => 'far fa-user',
                    'description' => 'Get exclusive market analysis from our pros and real-time signals via VIP Telegram groups.',
                    'image_one' => ''
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'iteratable',
                'name' => 'benefits',
                'content' => [
                    'title' => 'AI-Powered Market Forecasting',
                    'icon' => 'far fa-thumbs-up',
                    'description' => 'Let our advanced AI analyze sentiment, patterns, and correlations across markets.',
                    'image_one' => ''
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'iteratable',
                'name' => 'benefits',
                'content' => [
                    'title' => 'Seamless Autotrading Execution',
                    'icon' => 'far fa-chart-bar',
                    'description' => 'Set your rules once. Our system executes trades 24/7 with precision speed.',
                    'image_one' => ''
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'iteratable',
                'name' => 'benefits',
                'content' => [
                    'title' => 'Multi-Channel Alert System',
                    'icon' => 'far fa-envelope',
                    'description' => 'Receive critical trade signals via Telegram. Never miss a key market movement.',
                    'image_one' => ''
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'iteratable',
                'name' => 'benefits',
                'content' => [
                    'title' => 'Join a Growing Community',
                    'icon' => 'fas fa-users',
                    'description' => 'Connect with traders worldwide and share strategies.',
                    'image_one' => ''
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            
            // How it Works
            [
                'type' => 'iteratable',
                'name' => 'how_works',
                'content' => [
                    'title' => 'Create Account',
                    'description' => 'Simple registration process. No credit card required for trial.'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'iteratable',
                'name' => 'how_works',
                'content' => [
                    'title' => 'Select Package',
                    'description' => 'Choose your subscription plan. Flexible monthly or yearly options.'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'iteratable',
                'name' => 'how_works',
                'content' => [
                    'title' => 'Start Trading',
                    'description' => 'Activate autotrading and let the algo work for you.'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            
            // Socials
            [
                'type' => 'iteratable',
                'name' => 'socials',
                'content' => [
                    'icon' => 'fab fa-facebook-f',
                    'link' => 'https://facebook.com'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'iteratable',
                'name' => 'socials',
                'content' => [
                    'icon' => 'fab fa-twitter',
                    'link' => 'https://twitter.com'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'iteratable',
                'name' => 'socials',
                'content' => [
                    'icon' => 'fab fa-telegram-plane',
                    'link' => 'https://t.me/algoexperthub'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            
            // Team Members
            [
                'type' => 'iteratable',
                'name' => 'team',
                'content' => [
                    'member_name' => 'John Smith',
                    'designation' => 'Senior Trading Analyst',
                    'image_one' => '641bfff7802011679556599.jpg',
                    'facebook_url' => '#',
                    'twitter_url' => '#',
                    'linkedin_url' => '#',
                    'instagram_url' => '#'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'iteratable',
                'name' => 'team',
                'content' => [
                    'member_name' => 'Sarah Johnson',
                    'designation' => 'AI Strategy Developer',
                    'image_one' => '641c0078e59021679556728.png',
                    'facebook_url' => '#',
                    'twitter_url' => '#',
                    'linkedin_url' => '#',
                    'instagram_url' => '#'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'iteratable',
                'name' => 'team',
                'content' => [
                    'member_name' => 'Michael Chen',
                    'designation' => 'Risk Management Specialist',
                    'image_one' => '6424013aa49d51680081210.jpg',
                    'facebook_url' => '#',
                    'twitter_url' => '#',
                    'linkedin_url' => '#',
                    'instagram_url' => '#'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            
            // Testimonials
            [
                'type' => 'iteratable',
                'name' => 'testimonial',
                'content' => [
                    'client_name' => 'David Martinez',
                    'designation' => 'Professional Trader',
                    'description' => 'AlgoExpertHub has transformed my trading. The AI-powered signals are incredibly accurate, and the autotrading feature saves me hours every day.',
                    'image_one' => '641c01c8a57141679557064.jpg'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'iteratable',
                'name' => 'testimonial',
                'content' => [
                    'client_name' => 'Emily Thompson',
                    'designation' => 'Day Trader',
                    'description' => 'The multi-channel signal system is brilliant. I never miss important market movements, and the copy trading feature has been a game-changer.',
                    'image_one' => '642400bf486111680081087.jpg'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'iteratable',
                'name' => 'testimonial',
                'content' => [
                    'client_name' => 'Robert Williams',
                    'designation' => 'Crypto Trader',
                    'description' => 'Best trading platform I\'ve used. The combination of AI analysis and expert strategies gives me confidence in every trade.',
                    'image_one' => '6429214a26aa81680417098.jpg'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            
            // Blog Posts
            [
                'type' => 'iteratable',
                'name' => 'blog',
                'content' => [
                    'blog_title' => 'Understanding Algorithmic Trading: A Beginner\'s Guide',
                    'description' => 'Learn the fundamentals of algorithmic trading and how AI is revolutionizing the trading industry.',
                    'image_one' => '642151083c6211679905032.jpg'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'iteratable',
                'name' => 'blog',
                'content' => [
                    'blog_title' => 'Top 5 Trading Strategies for 2025',
                    'description' => 'Discover the most effective trading strategies that are shaping the market this year.',
                    'image_one' => '6421511901c511679905049.jpg'
                ],
                'theme' => $theme,
                'language_id' => 0
            ],
            [
                'type' => 'iteratable',
                'name' => 'blog',
                'content' => [
                    'blog_title' => 'Risk Management in Automated Trading',
                    'description' => 'Essential risk management techniques every automated trader should know to protect their capital.',
                    'image_one' => '6421513a781711679905082.jpg'
                ],
                'theme' => $theme,
                'language_id' => 0
            ]
        ];

        foreach ($contents as $contentData) {
            // Ensure all content has image_one property for blade compatibility
            if (is_array($contentData['content']) && !isset($contentData['content']['image_one'])) {
                $contentData['content']['image_one'] = '';
            }
            
            // For iteratable content, allow multiple items with same name
            // For non_iteratable, use updateOrCreate to prevent duplicates
            if ($contentData['type'] === 'iteratable') {
                // Check if this exact content already exists (by comparing content JSON)
                $contentJson = json_encode($contentData['content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $exists = Content::where('type', $contentData['type'])
                    ->where('name', $contentData['name'])
                    ->where('theme', $contentData['theme'])
                    ->where('language_id', $contentData['language_id'])
                    ->get()
                    ->contains(function ($item) use ($contentJson) {
                        $itemJson = json_encode($item->content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        return $itemJson === $contentJson;
                    });
                
                if (!$exists) {
                    Content::create($contentData);
                }
            } else {
                // For non_iteratable, use updateOrCreate to prevent duplicates
                Content::updateOrCreate(
                    [
                        'type' => $contentData['type'],
                        'name' => $contentData['name'],
                        'theme' => $contentData['theme'],
                        'language_id' => $contentData['language_id']
                    ],
                    $contentData
                );
            }
        }
    }
}

