<?php

namespace App\Filament\Resources\TourResource\Pages;

use App\Filament\Resources\TourResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
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

            if (Str::startsWith($localPath, 'uploads/') && Storage::disk('local')->exists($localPath)) {
                $ext = pathinfo($localPath, PATHINFO_EXTENSION) ?: 'webp';
                $s3Path = 'tours/main/' . Str::uuid() . '.' . $ext;

                $ok = Storage::disk('s3')->put($s3Path, Storage::disk('local')->get($localPath));

                if ($ok) {
                    Storage::disk('local')->delete($localPath);
                    $data['main_image_url'] = $s3Path;
                } else {
                    Log::error('S3 PUT FAILED (main_image_url)', [
                        'local' => $localPath,
                        's3' => $s3Path,
                    ]);
                    // dejamos el path local para que NO se rompa el registro
                }
            }
        }

        // GALLERY (local -> s3)
        if (!empty($data['images']) && is_array($data['images'])) {
            foreach ($data['images'] as $i => $img) {
                if (empty($img['url']) || !is_string($img['url'])) continue;

                $localPath = $img['url'];

                if (Str::startsWith($localPath, 'uploads/') && Storage::disk('local')->exists($localPath)) {
                    $ext = pathinfo($localPath, PATHINFO_EXTENSION) ?: 'webp';
                    $s3Path = 'tours/gallery/' . Str::uuid() . '.' . $ext;

                    $ok = Storage::disk('s3')->put($s3Path, Storage::disk('local')->get($localPath));

                    if ($ok) {
                        Storage::disk('local')->delete($localPath);
                        $data['images'][$i]['url'] = $s3Path;
                    } else {
                        Log::error('S3 PUT FAILED (gallery image)', [
                            'local' => $localPath,
                            's3' => $s3Path,
                            'index' => $i,
                        ]);
                    }
                }
            }
        }

        return $data;
    }
}