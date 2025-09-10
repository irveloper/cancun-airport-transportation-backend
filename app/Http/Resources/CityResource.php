<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'state' => $this->state,
            'country' => $this->country,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => $this->image,
            'active' => $this->active,
            'zones_count' => $this->whenCounted('zones'),
            'locations_count' => $this->whenCounted('locations'),
            'airports_count' => $this->whenCounted('airports'),
            'zones' => ZoneResource::collection($this->whenLoaded('zones')),
            'locations' => LocationResource::collection($this->whenLoaded('locations')),
            'airports' => AirportResource::collection($this->whenLoaded('airports')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
