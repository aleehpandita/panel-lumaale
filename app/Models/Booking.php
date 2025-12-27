<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'tour_id',
        'tour_date',
        'tour_departure_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'pax_adults',
        'pax_children',
        'pax_infants',
        'total_amount',
        'currency',
        'status',
        'payment_status',
        'payment_reference',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'tour_date'     => 'date',
        'total_amount'  => 'decimal:2',
        'pax_adults'    => 'integer',
        'pax_children'  => 'integer',
        'pax_infants'   => 'integer',
    ];

    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }

    public function departure()
    {
        return $this->belongsTo(TourDeparture::class, 'tour_departure_id');
    }

    public function payments()
    {
        return $this->hasMany(BookingPayment::class);
    }

    // Helpers opcionales útiles
    public function getTotalPaxAttribute(): int
    {
        return (int)$this->pax_adults + (int)$this->pax_children + (int)$this->pax_infants;
    }
}
