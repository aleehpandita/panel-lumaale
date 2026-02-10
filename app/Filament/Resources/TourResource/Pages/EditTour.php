<?php

namespace App\Filament\Resources\TourResource\Pages;

use App\Filament\Resources\TourResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EditTour extends EditRecord
{
    protected static string $resource = TourResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // 1) MAIN IMAGE
        if (!empty($data['main_image_url']) && str_starts_with($data['main_image_url'], 'uploads/')) {
            $data['main_image_url'] = $this->moveLocalToS3($data['main_image_url'], 'tours/main');
        }

        // 2) GALLERY
        if (!empty($data['images']) && is_array($data['images'])) {
            foreach ($data['images'] as $i => $img) {
                if (!empty($img['url']) && str_starts_with($img['url'], 'uploads/')) {
                    $data['images'][$i]['url'] = $this->moveLocalToS3($img['url'], 'tours/gallery');
                }
            }
        }

        return $data;
    }

    private function moveLocalToS3(string $localPath, string $s3Dir): string
    {
        if (!Storage::disk('local')->exists($localPath)) {
            Log::warning('Local file not found before S3 move', ['localPath' => $localPath]);
            return $localPath;
        }

        $filename = basename($localPath);
        $s3Path = trim($s3Dir, '/') . '/' . $filename;

        $stream = Storage::disk('local')->readStream($localPath);
        Storage::disk('s3')->writeStream($s3Path, $stream, ['visibility' => 'public']);
        if (is_resource($stream)) fclose($stream);

        Storage::disk('local')->delete($localPath);

        Log::info('Moved file local -> s3', ['local' => $localPath, 's3' => $s3Path]);

        return $s3Path;
    }
}