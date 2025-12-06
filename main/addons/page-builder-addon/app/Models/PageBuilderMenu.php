<?php

namespace Addons\PageBuilderAddon\App\Models;

use Illuminate\Database\Eloquent\Model;

class PageBuilderMenu extends Model
{
    protected $table = 'pagebuilder_menus';

    protected $fillable = [
        'name',
        'structure',
        'status',
    ];

    protected $casts = [
        'structure' => 'array',
    ];
}
