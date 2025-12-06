<?php

namespace Addons\PageBuilderAddon\App\Models;

use Illuminate\Database\Eloquent\Model;

class PageBuilderWidget extends Model
{
    protected $table = 'pagebuilder_widgets';
    
    protected $fillable = [
        'name',
        'slug',
        'title',
        'description',
        'icon',
        'category',
        'config',
        'html_template',
        'css_template',
        'js_template',
        'default_settings',
        'is_active',
        'is_pro',
        'order',
    ];

    protected $casts = [
        'config' => 'array',
        'default_settings' => 'array',
        'is_active' => 'boolean',
        'is_pro' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get widgets by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get active widgets
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get free widgets (non-pro)
     */
    public function scopeFree($query)
    {
        return $query->where('is_pro', false);
    }
}
