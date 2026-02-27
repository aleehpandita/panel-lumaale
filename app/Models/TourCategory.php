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
        return $this->belongsToMany(Tour::class, 'tour_tour_category');
    }
}
