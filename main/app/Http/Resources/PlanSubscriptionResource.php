<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlanSubscriptionResource extends BaseResource
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
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'is_current' => (bool) $this->is_current,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relationships
            'user' => UserResource::make($this->whenLoaded('user')),
            'plan' => PlanResource::make($this->whenLoaded('plan')),
            
            // Computed fields
            'is_active' => $this->status === 'active' && $this->is_current,
            'is_expired' => $this->end_date && $this->end_date->isPast(),
            'days_remaining' => $this->end_date ? max(0, now()->diffInDays($this->end_date, false)) : null,
        ];
    }
}