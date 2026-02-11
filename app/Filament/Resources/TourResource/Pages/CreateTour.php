<?php

namespace App\Filament\Resources\TourResource\Pages;

use App\Filament\Resources\TourResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateTour extends CreateRecord
{
    protected static string $resource = TourResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // MAIN IMAGE (local -> s3)
        if (!empty($data['main_image_url']) && is_string($data['main_image_url'])) {
            $localPath = $data['main_image_url'];

            if (Str::startsWith($localPath, 'uploads/')) {
                if (Storage::disk('local')->exists($localPath)) {
                    $ext = pathinfo($localPath, PATHINFO_EXTENSION) ?: 'webp';
                    $s3Path = 'tours/main/' . Str::uuid() . '.' . $ext;

                    Storage::disk('s3')->put(
                        $s3Path,
                        Storage::disk('local')->get($localPath),
                        ['visibility' => 'public']
                    );

                    Storage::disk('local')->delete($localPath);

                    $data['main_image_url'] = $s3Path;
                }
            }
        }

        // GALLERY IMAGES (local -> s3)
        if (!empty($data['images']) && is_array($data['images'])) {
            foreach ($data['images'] as $i => $img) {
                if (empty($img['url']) || !is_string($img['url'])) {
                    continue;
                }

                $localPath = $img['url'];

                if (Str::startsWith($localPath, 'uploads/')) {
                    if (Storage::disk('local')->exists($localPath)) {
                        $ext = pathinfo($localPath, PATHINFO_EXTENSION) ?: 'webp';
                        $s3Path = 'tours/gallery/' . Str::uuid() . '.' . $ext;

                        Storage::disk('s3')->put(
                            $s3Path,
                            Storage::disk('local')->get($localPath),
                            ['visibility' => 'public']
                        );

                        Storage::disk('local')->delete($localPath);

                        $data['images'][$i]['url'] = $s3Path;
                    }
                }
            }
        }

        return $data;
    }
}