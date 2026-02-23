<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DestinationListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $path = $this->main_image_path; // <-- tu columna real

        // IMPORTANTE: cambia 's3' por el disk real si usas otro
        $imageUrl = $path ? Storage::disk('s3')->url($path) : null;

        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'image' => $imageUrl,
            'tours_count' => (int) ($this->tours_count ?? 0),
        ];
    }
}