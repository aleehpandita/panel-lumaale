<?php

namespace App\Filament\Resources\DestinationResource\Pages;

use App\Filament\Resources\DestinationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateDestination extends CreateRecord
{
    protected static string $resource = DestinationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $value = $data['main_image_path'] ?? null;

        Log::info('### CREATE DESTINATION mutateFormDataBeforeCreate ###', ['value' => $value]);

        // Si no hay imagen, seguimos normal
        if (! $value || ! is_string($value)) {
            return $data;
        }

        // Si ya viene como destinations/... no hacemos nada
        if (str_starts_with($value, 'destinations/')) {
            return $data;
        }

        // Esperamos que venga como uploads/destinations/xxxxx.webp (local)
        if (str_starts_with($value, 'uploads/destinations/')) {
            $localDisk = Storage::disk('local');

            if (! $localDisk->exists($value)) {
                Log::warning('### CREATE DEST: LOCAL FILE NOT FOUND ###', ['path' => $value]);
                return $data;
            }

            $ext = pathinfo($value, PATHINFO_EXTENSION) ?: 'webp';
            $finalKey = 'destinations/' . Str::ulid() . '.' . $ext;

            // Sube a S3
            $contents = $localDisk->get($value);
            Storage::disk('s3')->put($finalKey, $contents, [
                'visibility' => 'private', // o 'public' si lo quieres público
                'ContentType' => $localDisk->mimeType($value) ?: 'image/webp',
            ]);

            // Borra archivo local
            $localDisk->delete($value);

            Log::info('### CREATE DEST: MOVED LOCAL -> S3 ###', ['to' => $finalKey]);

            // Guarda en DB el path final
            $data['main_image_path'] = $finalKey;
        }

        return $data;
    }
}