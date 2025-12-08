<?php

namespace App\Filament\Admin\Resources\AppVersionResource\Pages;

use App\Filament\Admin\Resources\AppVersionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateAppVersion extends CreateRecord
{
    protected static string $resource = AppVersionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 自动填充文件信息
        if (!empty($data['file_path'])) {
            $filePath = Storage::disk('public')->path($data['file_path']);

            if (file_exists($filePath)) {
                $data['file_name'] = basename($data['file_path']);
                $data['file_size'] = filesize($filePath);
                $data['sha512'] = hash_file('sha512', $filePath);
            }
        }

        return $data;
    }
}
