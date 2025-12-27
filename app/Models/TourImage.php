<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourImage extends Model
{
    protected $fillable = [
        'tour_id',
        'url',
        'sort_order',
    ];

    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }
}
