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
        // MAIN IMAGE: mover public/uploads/... -> S3/tours/main/...
        if (!empty($data['main_image_url']) && is_string($data['main_image_url'])) {
            $publicPath = $data['main_image_url'];

            if (Str::startsWith($publicPath, 'uploads/')) {
                if (Storage::disk('public')->exists($publicPath)) {
                    $ext = pathinfo($publicPath, PATHINFO_EXTENSION) ?: 'webp';
                    $s3Path = 'tours/main/' . Str::uuid() . '.' . $ext;

                    Storage::disk('s3')->put(
                        $s3Path,
                        Storage::disk('public')->get($publicPath),
                        ['visibility' => 'public']
                    );

                    Storage::disk('public')->delete($publicPath);

                    $data['main_image_url'] = $s3Path;
                }
            }
        }

        // GALLERY: mover cada uploads/... -> S3/tours/gallery/...
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