<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourPrice extends Model
{
    protected $fillable = [
        'tour_id',
        'name',
        'start_date',
        'end_date',
        'price_adult',
        'price_child',
        'price_infant',
        'currency',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        // decimal como string (Laravel suele manejarlos como string para precisión)
        'price_adult'  => 'decimal:2',
        'price_child'  => 'decimal:2',
        'price_infant' => 'decimal:2',
    ];

    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }
}
