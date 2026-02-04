<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Destination extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'main_image_path', // 👈 nuevo
    ];

    protected $appends = [
        'main_image_url', // 👈 para la API
    ];

    public function tours()
    {
        return $this->hasMany(Tour::class);
    }

    // 👇 URL pública de la imagen (S3 / Cloudflare)
    public function getMainImageUrlAttribute(): ?string
    {
        if (!$this->main_image_path) {
            return null;
        }

        return Storage::disk('s3')->url($this->main_image_path);
    }
}
