<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Subscription extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'member_id',
        'plan',
        'status',
        'starts_at',
        'expires_at',
        'daily_print_limit',
        'filters_enabled',
        'custom_template_enabled',
        'api_access_enabled',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'filters_enabled' => 'boolean',
            'custom_template_enabled' => 'boolean',
            'api_access_enabled' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * 订阅计划
     */
    const PLAN_FREE = 'free';
    const PLAN_PRO = 'pro';
    const PLAN_ENTERPRISE = 'enterprise';

    /**
     * 订阅状态
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';

    /**
     * 计划配置
     */
    public static function getPlanConfig(string $plan): array
    {
        $configs = [
            self::PLAN_FREE => [
                'name' => '免费版',
                'daily_print_limit' => 10,
                'filters_enabled' => false,
                'custom_template_enabled' => false,
                'api_access_enabled' => false,
            ],
            self::PLAN_PRO => [
                'name' => '专业版',
                'daily_print_limit' => 100,
                'filters_enabled' => true,
                'custom_template_enabled' => true,
                'api_access_enabled' => false,
            ],
            self::PLAN_ENTERPRISE => [
                'name' => '企业版',
                'daily_print_limit' => -1,
                'filters_enabled' => true,
                'custom_template_enabled' => true,
                'api_access_enabled' => true,
            ],
        ];

        return $configs[$plan] ?? $configs[self::PLAN_FREE];
    }

    /**
     * 获取会员
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * 检查订阅是否有效
     */
    public function isActive(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * 获取剩余天数
     */
    public function getDaysRemainingAttribute(): int
    {
        if (!$this->expires_at) {
            return 0;
        }

        $days = now()->diffInDays($this->expires_at, false);
        return max(0, (int) $days);
    }

    /**
     * 作用域：有效订阅
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * 为会员创建新订阅
     */
    public static function createForMember(
        Member $member,
        string $plan,
        int $durationDays = 30
    ): self {
        // 取消会员当前有效订阅
        self::where('member_id', $member->id)
            ->where('status', self::STATUS_ACTIVE)
            ->update(['status' => self::STATUS_CANCELLED]);

        $config = self::getPlanConfig($plan);

        $subscription = self::create([
            'member_id' => $member->id,
            'plan' => $plan,
            'status' => self::STATUS_ACTIVE,
            'starts_at' => now(),
            'expires_at' => $plan === self::PLAN_FREE ? null : now()->addDays($durationDays),
            'daily_print_limit' => $config['daily_print_limit'],
            'filters_enabled' => $config['filters_enabled'],
            'custom_template_enabled' => $config['custom_template_enabled'],
            'api_access_enabled' => $config['api_access_enabled'],
        ]);

        // 更新会员的订阅信息
        $member->update([
            'plan' => $plan,
            'subscription_expiry' => $subscription->expires_at,
        ]);

        return $subscription;
    }
}
