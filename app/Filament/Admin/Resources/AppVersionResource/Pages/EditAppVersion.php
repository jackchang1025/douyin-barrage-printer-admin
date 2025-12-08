<?php

namespace App\Filament\Admin\Resources\AppVersionResource\Pages;

use App\Filament\Admin\Resources\AppVersionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditAppVersion extends EditRecord
{
    protected static string $resource = AppVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // 如果文件路径改变，重新计算文件信息
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
