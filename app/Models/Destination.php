<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Destination extends Model
{
    protected $fillable = ['name','slug'];

    public function tours()
    {
        return $this->hasMany(Tour::class);
    }
}

