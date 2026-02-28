<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];
    protected $casts = [
        'name' => 'array',
    ];

    public function tours()
    {
        //return $this->belongsToMany(Tour::class, 'tour_tour_category');
        return $this->belongsToMany(Tour::class, 'tour_tour_category', 'tour_category_id', 'tour_id');
    }
     public function nameFor(string $locale = 'es'): string
    {
        $name = $this->name ?? [];
        if (is_array($name)) {
            return $name[$locale] ?? $name['es'] ?? $this->slug ?? '';
        }
        return (string) $name;
    }
}
