<?php

namespace Addons\PageBuilderAddon\App\Models;

use Illuminate\Database\Eloquent\Model;

class PageBuilderTemplate extends Model
{
    protected $table = 'pagebuilder_templates';

    protected $fillable = [
        'name',
        'description',
        'content',
        'preview_image',
        'category',
        'status',
    ];

    protected $casts = [
        'content' => 'array',
    ];
}
