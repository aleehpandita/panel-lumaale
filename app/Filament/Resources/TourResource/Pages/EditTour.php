<?php

namespace App\Filament\Resources\TourResource\Pages;

use App\Filament\Resources\TourResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EditTour extends EditRecord
{
    protected static string $resource = TourResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();

        // MAIN IMAGE: si no subiste una nueva, no la cambies
        if (empty($data['main_image_url'])) {
            $data['main_image_url'] = $record->main_image_url;
        } else {
            $publicPath = $data['main_image_url'];

            // Si viene de public/uploads..., mover a S3
            if (is_string($publicPath) && Str::startsWith($publicPath, 'uploads/')) {
                if (Storage::disk('public')->exists($publicPath)) {
                    $ext = pathinfo($publicPath, PATHINFO_EXTENSION) ?: 'webp';
                    $s3Path = 'tours/main/' . Str::uuid() . '.' . $ext;

                    Storage::disk('s3')->put(
                        $s3Path,
                        Storage::disk('public')->get($publicPath),
                        ['visibility' => 'public']
                    );

                    Storage::disk('public')->delete($publicPath);

                    // Opcional: borrar anterior en S3 si existía
                    if (!empty($record->main_image_url) && is_string($record->main_image_url)) {
                        Storage::disk('s3')->delete($record->main_image_url);
                    }

                    $data['main_image_url'] = $s3Path;
                }
            }
        }

        // GALLERY: mover solo las que vengan de public/uploads...
        if (!empty($data['images']) && is_array($data['images'])) {
            foreach ($data['images'] as $i => $img) {
                if (empty($img['url']) || !is_string($img['url'])) continue;

                $publicPath = $img['url'];

                if (Str::startsWith($publicPath, 'uploads/')) {
                    if (Storage::disk('public')->exists($publicPath)) {
                        $ext = pathinfo($publicPath, PATHINFO_EXTENSION) ?: 'webp';
                        $s3Path = 'tours/gallery/' . Str::uuid() . '.' . $ext;

                        Storage::disk('s3')->put(
                            $s3Path,
                            Storage::disk('public')->get($publicPath),
                            ['visibility' => 'public']
                        );

                        Storage::disk('public')->delete($publicPath);

                        $data['images'][$i]['url'] = $s3Path;
                    }
                }
            }
        }

        return $data;
    }
}