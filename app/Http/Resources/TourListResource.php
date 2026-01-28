<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TourListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $main = $this->main_image_url;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'short_description' => $this->short_description,
            'city' => $this->city,
            'duration_hours' => $this->duration_hours,
            'destination' => $this->destination?->name,
            'destination_slug' => $this->destination?->slug,

            // path guardado (por si lo ocupas)
            'main_image_path' => $main,

            // URL lista para frontend
            'main_image_url' => $main
                ? (str_starts_with($main, 'http') ? $main : Storage::url($main))
                : null,
        ];
    }
}
