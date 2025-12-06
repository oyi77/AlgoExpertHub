<?php

namespace Addons\PageBuilderAddon\App\Models;

use App\Models\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageBuilderPage extends Model
{
    protected $table = 'sp_pages'; // Laravel-Pagebuilder uses sp_pages table

    public $timestamps = true;

    protected $fillable = [
        'name',
        'title',
        'route',
        'layout',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Check if table exists before using
     */
    public static function tableExists(): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::hasTable('sp_pages');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Relationship to our Page model
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'id', 'pagebuilder_page_id');
    }
}
