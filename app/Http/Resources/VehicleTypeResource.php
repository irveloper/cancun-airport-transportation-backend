<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $request->header('Accept-Language', 'en');
        $locale = in_array($locale, ['en', 'es']) ? $locale : 'en';

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'image' => $this->image,
            'max_units' => $this->max_units,
            'max_pax' => $this->max_pax,
            'travel_time' => $this->travel_time,
            'video_url' => $this->video_url,
            'frame' => $this->frame,
            'active' => $this->active,
            'features' => $this->serviceFeatures->map(function ($feature) use ($locale) {
                return [
                    'id' => $feature->id,
                    'name' => $feature->getName($locale),
                    'description' => $feature->getDescription($locale),
                    'icon' => $feature->icon,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}