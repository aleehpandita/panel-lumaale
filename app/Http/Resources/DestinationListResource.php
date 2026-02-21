<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DestinationListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,                 // o JSON si lo manejas bilingüe
            'slug' => $this->slug,
            'image' => $this->image_url ?? $this->image ?? null, // ajusta al campo real
            'tours_count' => (int) ($this->tours_count ?? 0),
        ];
    }
}