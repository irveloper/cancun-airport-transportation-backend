<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceFeatureResource extends JsonResource
{
    private $locale;

    public function __construct($resource, $locale = 'en')
    {
        parent::__construct($resource);
        $this->locale = $locale;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->getName($this->locale),
            'description' => $this->getDescription($this->locale),
            'icon' => $this->icon,
        ];
    }
}