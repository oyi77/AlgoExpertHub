<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageSection extends Model
{
    use HasFactory;

    protected $casts = ['sections' => 'array'];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pageSection) {
            if (empty($pageSection->sections_hash) && !empty($pageSection->sections)) {
                $sectionsValue = is_array($pageSection->sections) 
                    ? json_encode($pageSection->sections) 
                    : $pageSection->sections;
                $pageSection->sections_hash = hash('sha256', $sectionsValue);
            }
        });

        static::updating(function ($pageSection) {
            if ($pageSection->isDirty('sections')) {
                $sectionsValue = is_array($pageSection->sections) 
                    ? json_encode($pageSection->sections) 
                    : $pageSection->sections;
                $pageSection->sections_hash = hash('sha256', $sectionsValue);
            }
        });
    }
}
