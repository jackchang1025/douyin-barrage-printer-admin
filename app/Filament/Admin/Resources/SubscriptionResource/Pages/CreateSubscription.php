<?php

namespace App\Filament\Admin\Resources\SubscriptionResource\Pages;

use App\Filament\Admin\Resources\SubscriptionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubscription extends CreateRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function afterCreate(): void
    {
        // 创建订阅后同步更新用户信息
        $subscription = $this->record;
        $subscription->user?->update([
            'plan' => $subscription->plan,
            'subscription_expiry' => $subscription->expires_at,
        ]);
    }
}

