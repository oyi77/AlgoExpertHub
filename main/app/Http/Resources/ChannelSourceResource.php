<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChannelSourceResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'is_admin_owned' => (bool) $this->is_admin_owned,
            'scope' => $this->scope,
            'status' => $this->status,
            'last_processed_at' => $this->last_processed_at?->toISOString(),
            'error_count' => $this->error_count,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Conditional fields
            'config' => $this->when($request->user()?->can('view', $this->resource), $this->config),
            'last_error' => $this->when($this->last_error, $this->last_error),
            
            // Relationships
            'user' => UserResource::make($this->whenLoaded('user')),
            'signals' => SignalResource::collection($this->whenLoaded('signals')),
            
            // Computed fields
            'is_active' => $this->status === 'active',
            'has_errors' => $this->error_count > 0,
        ];
    }
}