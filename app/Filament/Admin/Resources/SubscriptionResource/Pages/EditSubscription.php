<?php

namespace App\Filament\Admin\Resources\SubscriptionResource\Pages;

use App\Filament\Admin\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubscription extends EditRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // 保存订阅后同步更新用户信息
        $subscription = $this->record;
        if ($subscription->status === 'active') {
            $subscription->user?->update([
                'plan' => $subscription->plan,
                'subscription_expiry' => $subscription->expires_at,
            ]);
        }
    }
}

