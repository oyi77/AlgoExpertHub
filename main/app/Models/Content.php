<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    use HasFactory;

    protected $casts = [];

    /**
     * Accessor: Decode JSON string to object when reading
     */
    public function getContentAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, false);
            return $decoded !== null ? $decoded : (object)[];
        }
        return is_object($value) ? $value : (object)[];
    }

    /**
     * Mutator: Encode array/object to JSON string when writing
     */
    public function setContentAttribute($value)
    {
        if (is_array($value) || is_object($value)) {
            $this->attributes['content'] = json_encode($value);
        } else {
            $this->attributes['content'] = $value;
        }
    }


    public function images()
    {
        return $this->hasOne(FrontendMedia::class, 'content_id');
    }


    public function child()
    {
        return $this->hasMany(Content::class, 'parent_id');
    }
}
