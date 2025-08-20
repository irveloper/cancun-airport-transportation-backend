<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->vehicleType->id,
            'name' => $this->vehicleType->name,
            'pic' => $this->vehicleType->image,
            'type' => $this->vehicleType->code,
            'details' => $this->vehicleType->details,
            'detalles' => $this->vehicleType->detalles,
            'mUnits' => $this->vehicleType->max_units,
            'mPax' => $this->vehicleType->max_pax,
            'timeFromAirport' => $this->vehicleType->travel_time,
            'video' => $this->vehicleType->video_url,
            'frame' => $this->vehicleType->frame,
            'numVehicles' => $this->num_vehicles,
            'costVehicleOW' => number_format($this->cost_vehicle_one_way, 2, '.', ''),
            'totalOW' => (int) $this->total_one_way,
            'costVehicleRT' => number_format($this->cost_vehicle_round_trip, 2, '.', ''),
            'totalRT' => (int) $this->total_round_trip,
            'available' => $this->available ? 1 : 0
        ];
    }
}