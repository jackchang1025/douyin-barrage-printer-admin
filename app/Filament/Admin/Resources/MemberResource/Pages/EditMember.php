<?php

namespace App\Filament\Admin\Resources\MemberResource\Pages;

use App\Filament\Admin\Resources\MemberResource;
use App\Models\Member;
use App\Models\Plan;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMember extends EditRecord
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->modalHeading('删除会员')
                ->modalDescription(fn(Member $record): string => "确定要删除会员「{$record->nickname}」吗？此操作将同时删除该会员的所有订阅记录和登录令牌，且无法恢复。")
                ->modalSubmitActionLabel('确认删除')
                ->successNotificationTitle('会员已删除')
                ->before(function (Member $record) {
                    // 删除会员的所有订阅记录
                    $record->subscriptions()->delete();
                    // 删除会员的所有 API Token
                    $record->tokens()->delete();
                }),
        ];
    }

    /**
     * 保存后同步更新关联的订阅记录（仅更新计划信息）
     * 
     * 注意：subscription_expiry 现在由 subscriptions 表管理
     * 这里只需要在更换计划时同步更新订阅记录的 plan 信息
     */
    protected function afterSave(): void
    {
        /** @var Member $member */
        $member = $this->record;

        // 如果计划变更，同步更新订阅记录的计划信息
        $latestSubscription = $member->latestSubscription;

        if ($latestSubscription && $latestSubscription->plan_id !== $member->plan_id) {
            $plan = $member->planModel;
            if ($plan) {
                $latestSubscription->update([
                    'plan_id' => $plan->id,
                    'plan' => $plan->code,
                    'daily_print_limit' => $plan->daily_print_limit,
                    'filters_enabled' => $plan->filters_enabled,
                    'custom_template_enabled' => $plan->custom_template_enabled,
                    'api_access_enabled' => $plan->api_access_enabled,
                ]);
            }
        }
    }
}
