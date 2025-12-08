<?php

namespace App\Filament\Admin\Resources\MemberResource\Pages;

use App\Filament\Admin\Resources\MemberResource;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use Filament\Resources\Pages\CreateRecord;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;

    /**
     * 创建后自动创建关联的订阅记录
     * 
     * 订阅过期时间由计划的 duration_days 决定：
     * - duration_days = 0: 永久有效（expires_at = null）
     * - duration_days > 0: 从现在开始计算过期时间
     */
    protected function afterCreate(): void
    {
        /** @var Member $member */
        $member = $this->record;

        // 获取会员关联的计划
        $plan = $member->planModel ?? Plan::getDefault();

        if ($plan) {
            // 计算过期时间
            $expiresAt = null;
            if ($plan->duration_days > 0) {
                $expiresAt = now()->addDays($plan->duration_days);
            }

            // 创建订阅记录（单一数据源）
            Subscription::create([
                'member_id' => $member->id,
                'plan_id' => $plan->id,
                'plan' => $plan->code,
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => $expiresAt,
                'daily_print_limit' => $plan->daily_print_limit,
                'filters_enabled' => $plan->filters_enabled,
                'custom_template_enabled' => $plan->custom_template_enabled,
                'api_access_enabled' => $plan->api_access_enabled,
            ]);
        }
    }
}
