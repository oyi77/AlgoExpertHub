<?php

namespace Addons\PageBuilderAddon\App\Models;

use Illuminate\Database\Eloquent\Model;

class PageBuilderLayout extends Model
{
    protected $table = 'pagebuilder_layouts';
    
    protected $fillable = [
        'name',
        'slug',
        'title',
        'description',
        'type',
        'structure',
        'settings',
        'is_default',
        'is_active',
        'order',
    ];

    protected $casts = [
        'structure' => 'array',
        'settings' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get layouts by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get active layouts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get default layout
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
