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
     * Convert object to array recursively, handling all edge cases
     * 
     * @param mixed $data Data to convert
     * @return array|mixed Converted array or original value
     */
    private static function objectToArray($data)
    {
        if (is_object($data)) {
            // Handle stdClass and other objects
            $data = get_object_vars($data);
        }
        
        if (is_array($data)) {
            // Recursively convert nested objects
            return array_map([self::class, 'objectToArray'], $data);
        }
        
        return $data;
    }

    /**
     * Safely convert value to string, handling objects and other types
     * 
     * @param mixed $value Value to convert
     * @return string String representation
     */
    private static function toString($value)
    {
        if (is_string($value)) {
            return $value;
        }
        
        if (is_numeric($value)) {
            return (string) $value;
        }
        
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        
        if (is_null($value)) {
            return '';
        }
        
        if (is_object($value)) {
            // Try to get string representation
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }
            // If object has a 'key' or 'name' property, use that
            if (isset($value->key)) {
                return self::toString($value->key);
            }
            if (isset($value->name)) {
                return self::toString($value->name);
            }
            // Last resort: return class name
            return get_class($value);
        }
        
        if (is_array($value)) {
            // For arrays, return first element if available
            return !empty($value) ? self::toString(reset($value)) : '';
        }
        
        // Fallback: try casting
        try {
            return (string) $value;
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Translate a string using Laravel's translation system
     * 
     * @param string|mixed $key The translation key or default text
     * @param array|object $replace Optional replacement values (array or object)
     * @param string|null $locale Optional locale
     * @return string Translated string
     */
    public static function trans($key, $replace = [], $locale = null)
    {
        // Safely convert $key to string, handling objects and other types
        $key = self::toString($key);
        
        // If key is empty after conversion, return empty string
        if (empty($key)) {
            return '';
        }
        
        // Convert object to array for PHP 8.4 compatibility
        // This is critical for PHP 8.4 which has stricter type checking
        if (is_object($replace)) {
            // Use our custom conversion method that handles all edge cases
            $replace = self::objectToArray($replace);
        }
        
        // Ensure $replace is always an array
        if (!is_array($replace)) {
            $replace = [];
        }
        
        // Clean up $replace array - ensure all values are strings or primitives
        $replace = array_map(function($value) {
            if (is_object($value)) {
                return self::toString($value);
            }
            if (is_array($value)) {
                return self::objectToArray($value);
            }
            return $value;
        }, $replace);
        
        // Use the global trans() helper which handles PHP 8.4 better
        try {
            if ($locale !== null) {
                return \trans($key, $replace, $locale);
            }
            return \trans($key, $replace);
        } catch (\TypeError $e) {
            // Fallback: return the key itself if translation fails
            return $key;
        } catch (\Exception $e) {
            // Fallback for any other translation errors
            return $key;
        }
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
