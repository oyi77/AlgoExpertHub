<?php

namespace Addons\AiConnectionAddon\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiProvider extends Model
{
    use HasFactory;

    protected $table = 'ai_providers';

    protected $fillable = [
        'name',
        'slug',
        'status',
        'default_connection_id',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Get all connections for this provider
     */
    public function connections()
    {
        return $this->hasMany(AiConnection::class, 'provider_id');
    }

    /**
     * Get the default connection for this provider
     */
    public function defaultConnection()
    {
        return $this->belongsTo(AiConnection::class, 'default_connection_id');
    }

    /**
     * Get active connections only
     */
    public function activeConnections()
    {
        return $this->connections()->where('status', 'active')->orderBy('priority');
    }

    /**
     * Scope: Active providers only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: By slug
     */
    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    /**
     * Check if provider is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get provider display name with status
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name . ($this->isActive() ? '' : ' (Inactive)');
    }
}

