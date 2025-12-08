<?php

namespace App\Filament\Admin\Resources\PlanResource\Pages;

use App\Filament\Admin\Resources\PlanResource;
use App\Models\Plan;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlan extends EditRecord
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (Plan $record) {
                    if ($record->subscriptions()->exists() || $record->members()->exists()) {
                        throw new \Exception("此计划正在被使用，无法删除");
                    }
                    if ($record->is_default) {
                        throw new \Exception("无法删除默认计划");
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // 如果设置为默认，取消其他默认计划
        if ($data['is_default'] ?? false) {
            Plan::where('is_default', true)
                ->where('id', '!=', $this->record->id)
                ->update(['is_default' => false]);
        }

        return $data;
    }
}

