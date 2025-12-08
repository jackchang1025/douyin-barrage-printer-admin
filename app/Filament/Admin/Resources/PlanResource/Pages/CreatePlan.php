<?php

namespace App\Filament\Admin\Resources\PlanResource\Pages;

use App\Filament\Admin\Resources\PlanResource;
use App\Models\Plan;
use Filament\Resources\Pages\CreateRecord;

class CreatePlan extends CreateRecord
{
    protected static string $resource = PlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 如果设置为默认，取消其他默认计划
        if ($data['is_default'] ?? false) {
            Plan::where('is_default', true)->update(['is_default' => false]);
        }

        return $data;
    }
}

