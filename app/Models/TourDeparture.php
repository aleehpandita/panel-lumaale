<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourDeparture extends Model
{
    protected $fillable = [
        'tour_id',
        'departure_time',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'tour_departure_id');
    }
}
