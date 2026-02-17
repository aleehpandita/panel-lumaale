<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TourImage extends Model
{
    protected $table = 'tour_images';

    protected $fillable = [
        'tour_id',
        'url',
        'sort_order',
    ];

    protected static function booted()
    {
        static::saving(function (TourImage $image) {
            $image->url = self::moveLocalToS3IfNeeded($image->url);
        });
    }

    private static function moveLocalToS3IfNeeded(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        // Si ya está en S3 (guardas algo como "tours/gallery/xxx.webp"), no tocar
        if (str_starts_with($path, 'tours/')) {
            return $path;
        }

        // Si guardaste una URL absoluta por error, no tocar
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Solo movemos lo que venga de uploads/tours/gallery
        if (! str_starts_with($path, 'uploads/tours/gallery/')) {
            return $path;
        }

        // Si no existe en local, no tocar (evita 500)
        if (! Storage::disk('local')->exists($path)) {
            return $path;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: 'webp');
        $key = 'tours/gallery/' . Str::uuid() . '.' . $ext;

        $stream = Storage::disk('local')->readStream($path);

        // Sube a S3 (si tu bucket bloquea público, da igual: esto solo GUARDA)
        Storage::disk('s3')->put($key, $stream, [
            'ContentType' => match ($ext) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                default => 'application/octet-stream',
            },
        ]);

        if (is_resource($stream)) {
            fclose($stream);
        }

        // Borra el archivo local para no acumular basura
        Storage::disk('local')->delete($path);

        // Ahora la BD guardará "tours/gallery/....webp"
        return $key;
    }

    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }
}