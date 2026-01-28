<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TourResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $main = $this->main_image_url;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'short_description' => $this->short_description,
            'long_description' => $this->long_description,
            'duration_hours' => $this->duration_hours,
            'city' => $this->city,
            'meeting_point' => $this->meeting_point,
            'status' => $this->status,
            'min_people' => $this->min_people,
            'max_people' => $this->max_people,
            // path guardado (opcional)
            'main_image_path' => $main,
            // URL lista para frontend
            'main_image_url' => $main
                ? (str_starts_with($main, 'http') ? $main : \Illuminate\Support\Facades\Storage::url($main))
                : null,
            'included' => $this->included ?? [],
            'not_included' => $this->not_included ?? [],

            'categories' => $this->categories->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
            ]),
            'destination' => $this->destination ? [
                'id' => $this->destination->id,
                'name' => $this->destination->name,
                'slug' => $this->destination->slug,
            ] : null,
            'images' => $this->images->map(fn($img) => [
                'id' => $img->id,
                'path' => $img->url,
                'url' => $img->url
                    ? (str_starts_with($img->url, 'http') ? $img->url : \Illuminate\Support\Facades\Storage::url($img->url))
                    : null,
                'sort_order' => $img->sort_order,
            ]),

            'departures' => $this->departures->where('is_active', true)->values()->map(fn($d) => [
                'id' => $d->id,
                'departure_time' => $d->departure_time,
            ]),

            'prices' => $this->prices->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'start_date' => optional($p->start_date)->toDateString(),
                'end_date' => optional($p->end_date)->toDateString(),
                'price_adult' => $p->price_adult,
                'price_child' => $p->price_child,
                'price_infant' => $p->price_infant,
                'currency' => $p->currency,
            ]),
        ];
    }
}
