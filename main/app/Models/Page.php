<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $casts = ['seo_keywords' => 'array'];

    public function widgets()
    {
        return $this->hasMany(PageSection::class,'page_id');
    }

    /**
     * Relationship to pagebuilder page (if exists)
     */
    public function pagebuilderPage()
    {
        // Check if pagebuilder_pages table exists and link exists
        if ($this->pagebuilder_page_id) {
            try {
                if (class_exists(\Addons\PageBuilderAddon\App\Models\PageBuilderPage::class)) {
                    if (\Addons\PageBuilderAddon\App\Models\PageBuilderPage::tableExists()) {
                        return $this->belongsTo(\Addons\PageBuilderAddon\App\Models\PageBuilderPage::class, 'pagebuilder_page_id', 'id');
                    }
                }
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }
}
