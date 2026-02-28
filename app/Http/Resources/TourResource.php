<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;


class TourResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = request('locale')
            ?: substr((string) request()->header('Accept-Language'), 0, 2)
            ?: app()->getLocale()
            ?: 'es';

        $lang = in_array($lang, ['es', 'en'], true) ? $lang : 'es';
        $fallback = $lang === 'en' ? 'es' : 'en';

        $main = $this->main_image_url;

        $pick = function ($value) use ($lang, $fallback) {
            // Si ya viene como JSON array {es,en}
            if (is_array($value)) {
                return $value[$lang] ?? $value[$fallback] ?? null;
            }
            return $value; // compat si fuera string/text viejo
        };

        $pickList = function ($value) use ($lang, $fallback) {
            // Formato correcto: { es:[], en:[] }
            if (is_array($value) && (array_key_exists('es', $value) || array_key_exists('en', $value))) {
                $list = $value[$lang] ?? $value[$fallback] ?? [];
                return is_array($list) ? array_values($list) : [];
            }

            // Compat por si antes guardabas repeater como [{item:"..."}, ...]
            if (is_array($value)) {
                $maybeObjects = $value;
                if (isset($maybeObjects[0]) && is_array($maybeObjects[0]) && array_key_exists('item', $maybeObjects[0])) {
                    return array_values(array_filter(array_map(fn ($r) => $r['item'] ?? null, $maybeObjects)));
                }
                // Si ya era array simple ["a","b"]
                return array_values($value);
            }

            return [];
        };

        return [
            'id' => $this->id,
            'slug' => $this->slug,

            // i18n (aplanado para frontend)
            'title' => $pick($this->title),
            'short_description' => $pick($this->short_description),
            'long_description' => $pick($this->long_description),
            'meeting_point' => $pick($this->meeting_point),

            // No i18n
            'duration_hours' => $this->duration_hours,
            'city' => $this->city,
            'status' => $this->status,
            'min_people' => $this->min_people,
            'max_people' => $this->max_people,

            // Imagen principal
            'main_image_path' => $main,
            'main_image_url' => $main
                ? (str_starts_with($main, 'http') ? $main : Storage::url($main))
                : null,

            // Listas i18n
            'included' => $pickList($this->included),
            'not_included' => $pickList($this->not_included),

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
                    ? (str_starts_with($img->url, 'http') ? $img->url : Storage::url($img->url))
                    : null,
                'sort_order' => $img->sort_order,
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
            'operating_days' => $this->operating_days ?? [],
            'departures' => $this->departures->where('is_active', true)->values()->map(fn($d) => [
                'id' => $d->id,
                'departure_time' => $d->departure_time,
            ]),
        ];
    }
}
