<?php

namespace App\Filament\Resources\TourResource\Pages;

use App\Filament\Resources\TourResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateTour extends CreateRecord
{
    protected static string $resource = TourResource::class;

    /**
     * Mueve uploads/... (local) => tours/... (s3)
     */
    protected function moveLocalToS3(?string $path, string $prefix): ?string
    {
        if (! $path) return null;

        // ya está en S3
        if (str_starts_with($path, rtrim($prefix, '/') . '/')) {
            return $path;
        }
        if (str_starts_with($path, 'tours/')) {
            return $path;
        }

        // solo movemos uploads/...
        if (! str_starts_with($path, 'uploads/')) {
            return $path;
        }

        if (! Storage::disk('local')->exists($path)) {
            return $path;
        }

        $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'webp';
        $key = rtrim($prefix, '/') . '/' . (string) Str::uuid() . '.' . $ext;

        $stream = Storage::disk('local')->readStream($path);

        Storage::disk('s3')->put($key, $stream, [
            'visibility' => 'public',
            'ContentType' => match (strtolower($ext)) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                default => 'application/octet-stream',
            },
        ]);

        if (is_resource($stream)) {
            fclose($stream);
        }

        Storage::disk('local')->delete($path);

        return $key;
    }

    /**
     * Después de crear el Tour, Filament ya guardó las relaciones (images).
     * Aquí movemos cada imagen del gallery a S3 y actualizamos el campo url.
     */
    protected function afterCreate(): void
    {
        $tour = $this->record;

        // Mover GALERÍA
        if (method_exists($tour, 'images')) {
            $tour->load('images');

            foreach ($tour->images as $img) {
                $old = $img->url ?? null;

                // Solo si viene de local uploads/...
                if (is_string($old) && str_starts_with($old, 'uploads/')) {
                    $new = $this->moveLocalToS3($old, 'tours/gallery');

                    if ($new && $new !== $old) {
                        $img->url = $new;
                        $img->save();
                    }
                }
            }
        }
    }
}