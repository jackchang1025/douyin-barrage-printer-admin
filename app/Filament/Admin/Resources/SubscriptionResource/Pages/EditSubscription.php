<?php

namespace App\Filament\Admin\Resources\SubscriptionResource\Pages;

use App\Filament\Admin\Resources\SubscriptionResource;
use App\Models\Subscription;
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

    /**
     * 保存后同步更新关联的会员记录
     * 
     * 注意：subscription_expiry 已从 members 表移除
     * 现在由 subscriptions 表统一管理，这里只需同步计划信息
     */
    protected function afterSave(): void
    {
        /** @var Subscription $subscription */
        $subscription = $this->record;

        // 同步更新会员的计划信息（仅计划，不包含过期时间）
        $subscription->member?->update([
            'plan_id' => $subscription->plan_id,
            'plan' => $subscription->plan,
        ]);
    }
}

