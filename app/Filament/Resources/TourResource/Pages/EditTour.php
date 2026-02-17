<?php

namespace App\Filament\Resources\TourResource\Pages;

use App\Filament\Resources\TourResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EditTour extends EditRecord
{
    protected static string $resource = TourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Mueve uploads/... (local) => tours/... (s3)
     */
    protected function moveLocalToS3(?string $path, string $prefix): ?string
    {
        if (! $path) return null;

        // ya está en S3
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
     * Después de guardar el Tour y sus relaciones, movemos la galería a S3.
     */
    protected function afterSave(): void
    {
        $tour = $this->record;

        if (method_exists($tour, 'images')) {
            $tour->load('images');

            foreach ($tour->images as $img) {
                $old = $img->url ?? null;

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