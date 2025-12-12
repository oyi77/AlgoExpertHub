<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends BaseResource
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
            'description' => $this->description,
            'price' => number_format($this->price, 2),
            'plan_type' => $this->plan_type,
            'duration' => $this->duration,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Conditional fields
            'image' => $this->when($this->image, asset('storage/' . $this->image)),
            
            // Relationships
            'signals' => SignalResource::collection($this->whenLoaded('signals')),
            'subscriptions' => PlanSubscriptionResource::collection($this->whenLoaded('subscriptions')),
            
            // Computed fields
            'is_lifetime' => $this->plan_type === 'lifetime',
            'duration_text' => $this->plan_type === 'lifetime' ? 'Lifetime' : $this->duration . ' days',
        ];
    }
}