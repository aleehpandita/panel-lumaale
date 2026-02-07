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

        Log::info('### DEST SAVE ###', ['value' => $value]);

        if (! $value || ! is_string($value)) {
            return $data;
        }

        // Si ya es ruta final, no hacemos nada
        if (str_starts_with($value, 'destinations/')) {
            return $data;
        }

        // Si viene como filename (tu caso)
        if (! str_contains($value, '/')) {
            $tmp = 'livewire-tmp/' . $value;

            if (Storage::disk('s3')->exists($tmp)) {
                $ext = pathinfo($tmp, PATHINFO_EXTENSION) ?: 'webp';
                $final = 'destinations/' . Str::ulid() . '.' . $ext;

                Storage::disk('s3')->copy($tmp, $final);
                Storage::disk('s3')->delete($tmp);

                Log::info('### MOVED TMP -> DEST ###', ['from' => $tmp, 'to' => $final]);

                $data['main_image_path'] = $final;
            } else {
                Log::warning('### TMP NOT FOUND ###', ['tmp' => $tmp]);
            }

            return $data;
        }

        // Si viniera como livewire-tmp/..., también lo soportamos
        if (str_starts_with($value, 'livewire-tmp/')) {
            $ext = pathinfo($value, PATHINFO_EXTENSION) ?: 'webp';
            $final = 'destinations/' . Str::ulid() . '.' . $ext;

            Storage::disk('s3')->copy($value, $final);
            Storage::disk('s3')->delete($value);

            Log::info('### MOVED TMPPATH -> DEST ###', ['from' => $value, 'to' => $final]);

            $data['main_image_path'] = $final;
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