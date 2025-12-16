<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends BaseResource
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
            'username' => $this->username,
            'email' => $this->email,
            'balance' => number_format($this->balance, 2),
            'status' => $this->status,
            'is_email_verified' => (bool) $this->is_email_verified,
            'kyc_status' => $this->kyc_status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Conditional fields
            'email_verified_at' => $this->when($this->email_verified_at, $this->email_verified_at?->toISOString()),
            'address' => $this->when($this->address, $this->address),
            
            // Relationships
            'current_plan' => PlanSubscriptionResource::make($this->whenLoaded('currentplan')),
            'subscriptions' => PlanSubscriptionResource::collection($this->whenLoaded('subscriptions')),
        ];
    }
}