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

    if (! $value || ! is_string($value)) {
        return $data;
    }

    // ya final
    if (str_starts_with($value, 'destinations/')) {
        return $data;
    }

    // En local te va a llegar algo como: livewire-tmp/archivo.webp
    $localDisk = Storage::disk('public');

    if ($localDisk->exists($value)) {
        $ext = pathinfo($value, PATHINFO_EXTENSION) ?: 'webp';
        $final = 'destinations/' . Str::ulid() . '.' . $ext;

        // subir a s3
        Storage::disk('s3')->put($final, $localDisk->get($value), 'public');

        // borrar local tmp
        $localDisk->delete($value);

        Log::info('MOVED LOCAL TMP -> S3', ['from' => $value, 'to' => $final]);

        $data['main_image_path'] = $final;
    } else {
        Log::warning('LOCAL TMP NOT FOUND', ['path' => $value]);
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