<?php

namespace App\Filament\Resources\DestinationResource\Pages;

use App\Filament\Resources\DestinationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EditDestination extends EditRecord
{
    protected static string $resource = DestinationResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $value = $data['main_image_path'] ?? null;

        Log::info('### EDIT DESTINATION mutateFormDataBeforeSave ###', ['value' => $value]);

        if (! $value || ! is_string($value)) {
            return $data;
        }

        // Si ya es S3 final, no hacemos nada
        if (str_starts_with($value, 'destinations/')) {
            return $data;
        }

        // Si viene como uploads/destinations/xxxxx.webp (local)
        if (str_starts_with($value, 'uploads/destinations/')) {
            $localDisk = Storage::disk('local');

            if (! $localDisk->exists($value)) {
                Log::warning('### EDIT DEST: LOCAL FILE NOT FOUND ###', ['path' => $value]);
                return $data;
            }

            $ext = pathinfo($value, PATHINFO_EXTENSION) ?: 'webp';
            $finalKey = 'destinations/' . Str::ulid() . '.' . $ext;

            // Subimos a S3
            $contents = $localDisk->get($value);
            Storage::disk('s3')->put($finalKey, $contents, [
                'visibility' => 'private', // o 'public'
                'ContentType' => $localDisk->mimeType($value) ?: 'image/webp',
            ]);

            // Borra el local tmp
            $localDisk->delete($value);

            // Borra imagen anterior en S3 si existía y era destinations/
            $old = $this->record?->main_image_path;
            if (is_string($old) && str_starts_with($old, 'destinations/') && Storage::disk('s3')->exists($old)) {
                Storage::disk('s3')->delete($old);
                Log::info('### EDIT DEST: DELETED OLD S3 FILE ###', ['old' => $old]);
            }

            Log::info('### EDIT DEST: MOVED LOCAL -> S3 ###', ['to' => $finalKey]);

            $data['main_image_path'] = $finalKey;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}