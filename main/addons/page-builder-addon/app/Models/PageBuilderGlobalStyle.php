<?php

namespace Addons\PageBuilderAddon\App\Models;

use Illuminate\Database\Eloquent\Model;

class PageBuilderGlobalStyle extends Model
{
    protected $table = 'pagebuilder_global_styles';
    
    protected $fillable = [
        'name',
        'type',
        'content',
        'settings',
        'is_active',
        'order',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get styles by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get active styles
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
