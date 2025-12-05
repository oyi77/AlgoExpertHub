<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory, Searchable;

    public $searchable = ['plan_name'];

    protected $casts = [];

    /**
     * Accessor: Decode feature JSON string to array
     */
    public function getFeatureAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true); // true = return array
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
        return is_array($value) ? $value : [];
    }

    /**
     * Mutator: Encode feature array to JSON string
     */
    public function setFeatureAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['feature'] = json_encode($value);
        } else {
            $this->attributes['feature'] = $value;
        }
    }

    public function subscriptions()
    {
        return $this->hasMany(PlanSubscription::class,'plan_id');
    }

    public function signals()
    {
        return $this->belongsToMany(Signal::class, 'plan_signals');
    }
}
