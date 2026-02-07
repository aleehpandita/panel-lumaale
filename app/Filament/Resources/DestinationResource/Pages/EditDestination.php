<?php

namespace App\Filament\Resources\DestinationResource\Pages;

use App\Filament\Resources\DestinationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EditDestination extends EditRecord
{
    protected static string $resource = DestinationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeSave(array $data): array
{
    Log::info('### MUTATE BEFORE SAVE HIT ###');

    if (($data['main_image_path'] ?? null) instanceof TemporaryUploadedFile) {
        /** @var TemporaryUploadedFile $file */
        $file = $data['main_image_path'];

        $ext = $file->getClientOriginalExtension() ?: 'webp';
        $name = Str::ulid() . '.' . $ext;

        $data['main_image_path'] = $file->storePubliclyAs('destinations', $name, 's3');

        Log::info('### MOVED TO DESTINATIONS ###', ['path' => $data['main_image_path']]);
    }

    return $data;
}
}
