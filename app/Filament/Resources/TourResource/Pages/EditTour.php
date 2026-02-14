<?php

namespace App\Filament\Resources\TourResource\Pages;

use App\Filament\Resources\TourResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EditTour extends EditRecord
{
    protected static string $resource = TourResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();

        // MAIN IMAGE
        if (empty($data['main_image_url'])) {
            $data['main_image_url'] = $record->main_image_url;
        } else {
            $localPath = $data['main_image_url'];

            if (is_string($localPath) && Str::startsWith($localPath, 'uploads/') && Storage::disk('local')->exists($localPath)) {
                $ext = pathinfo($localPath, PATHINFO_EXTENSION) ?: 'webp';
                $s3Path = 'tours/main/' . Str::uuid() . '.' . $ext;

                $ok = Storage::disk('s3')->put($s3Path, Storage::disk('local')->get($localPath));

                if ($ok) {
                    Storage::disk('local')->delete($localPath);

                    // Borra anterior solo si el nuevo sí subió
                    if (!empty($record->main_image_url) && is_string($record->main_image_url)) {
                        Storage::disk('s3')->delete($record->main_image_url);
                    }

                    $data['main_image_url'] = $s3Path;
                } else {
                    Log::error('S3 PUT FAILED (main_image_url edit)', [
                        'local' => $localPath,
                        's3' => $s3Path,
                        'record_id' => $record->id ?? null,
                    ]);

                    // No rompas: conserva el anterior
                    $data['main_image_url'] = $record->main_image_url;
                }
            }
        }

        // GALLERY
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
                        Log::error('S3 PUT FAILED (gallery edit)', [
                            'local' => $localPath,
                            's3' => $s3Path,
                            'index' => $i,
                            'record_id' => $record->id ?? null,
                        ]);
                    }
                }
            }
        }

        return $data;
    }
}