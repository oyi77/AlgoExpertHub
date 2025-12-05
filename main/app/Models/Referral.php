<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;

    protected $casts = [];

    public function getLevelAttribute($value)
    {
        if ($value === null) {
            return [];
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($value) ? $value : [];
    }

    public function setLevelAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['level'] = json_encode($value);
        } else {
            $this->attributes['level'] = $value;
        }
    }

    public function getCommissionAttribute($value)
    {
        if ($value === null) {
            return [];
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($value) ? $value : [];
    }

    public function setCommissionAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['commission'] = json_encode($value);
        } else {
            $this->attributes['commission'] = $value;
        }
    }
}
