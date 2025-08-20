<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
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
            'type' => $this->type,
            'type_name' => $this->type_name,
            'active' => $this->active,
            'description' => $this->description,
            'coordinates' => $this->when(
                $this->latitude && $this->longitude,
                [
                    'latitude' => $this->latitude,
                    'longitude' => $this->longitude,
                ]
            ),
            'city' => new CityResource($this->whenLoaded('city')),
            'city_id' => $this->city_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
