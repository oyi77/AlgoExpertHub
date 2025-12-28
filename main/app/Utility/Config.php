<?php

namespace App\Utility;


class Config
{

    public static function sections()
    {
        return [
            'banner',
            'about',
            'trade',
            'how_works',
            'plans',
            'why_choose_us',
            'overview',
            'benefits',
            'testimonial',
            'referral',
            'team',
            'brand',
            'blog',
            'contact',
            'footer',
            'socials',
            'links',
            'auth'
        ];
    }

    public static function sectionsSelectable()
    {
        return [
            'about',
            'benefits',
            'trade',
            'blog',
            'contact',
            'how_works',
            'overview',
            'plans',
            'referral',
            'team',
            'testimonial',
            'why_choose_us'
        ];
    }

    /**
     * Translate a string using Laravel's translation system
     * 
     * @param string $key The translation key or default text
     * @param array $replace Optional replacement values
     * @param string|null $locale Optional locale
     * @return string Translated string
     */
    public static function trans($key, $replace = [], $locale = null)
    {
        return __($key, $replace, $locale);
    }

    /**
     * Get a configuration value from the builder
     * 
     * @param string $key The configuration key
     * @return mixed Configuration value
     */
    public static function builder($key)
    {
        return \App\Helpers\Helper\Helper::builder($key);
    }
}
