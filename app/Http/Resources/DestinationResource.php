<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DestinationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'image' => $this->image_url ?? $this->image ?? null,
            'description' => $this->description ?? null, // si existe (JSON ES/EN)
            'tours_count' => (int) ($this->tours_count ?? 0),
        ];
    }
}