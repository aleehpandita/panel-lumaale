<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'short_description',
        'long_description',
        'duration_hours',
        'city',
        'meeting_point',
        'status',
        'min_people',
        'max_people',
        'main_image_url',
        'included',
        'not_included',
    ];

    protected $casts = [
        'title' => 'array',
        'short_description' => 'array',
        'long_description' => 'array',
        'meeting_point' => 'array',
        'included' => 'array',
        'not_included' => 'array',
    ];
    public function i18n(string $field, string $lang = 'es', $fallback = 'es')
    {
        $value = $this->{$field};

        if (!is_array($value)) return $value;

        return $value[$lang]
            ?? $value[$fallback]
            ?? null;
    }
    public function categories()
    {
        // Pivot: tour_tour_category (tour_id, tour_category_id)
        return $this->belongsToMany(TourCategory::class, 'tour_tour_category');
    }

    public function images()
    {
        return $this->hasMany(TourImage::class)->orderBy('sort_order');
    }

    public function departures()
    {
        return $this->hasMany(TourDeparture::class)->orderBy('departure_time');
    }

    public function prices()
    {
        return $this->hasMany(TourPrice::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }
}
