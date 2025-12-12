<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SignalResource extends BaseResource
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
            'title' => $this->title,
            'description' => $this->description,
            'open_price' => number_format($this->open_price, 5),
            'sl' => number_format($this->sl, 5),
            'tp' => number_format($this->tp, 5),
            'direction' => $this->direction,
            'is_published' => (bool) $this->is_published,
            'auto_created' => (bool) $this->auto_created,
            'published_date' => $this->published_date?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Conditional fields
            'image' => $this->when($this->image, asset('storage/' . $this->image)),
            'message_hash' => $this->when($this->message_hash, $this->message_hash),
            
            // Relationships
            'currency_pair' => CurrencyPairResource::make($this->whenLoaded('pair')),
            'time_frame' => TimeFrameResource::make($this->whenLoaded('time')),
            'market' => MarketResource::make($this->whenLoaded('market')),
            'plans' => PlanResource::collection($this->whenLoaded('plans')),
            'channel_source' => ChannelSourceResource::make($this->whenLoaded('channelSource')),
        ];
    }
}