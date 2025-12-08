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
        'plan_id',
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
     * 计划配置 - 优先从数据库获取，兼容旧代码
     */
    public static function getPlanConfig(string|int $plan): array
    {
        // 如果是数字（plan_id），直接从数据库获取
        if (is_numeric($plan)) {
            $planModel = Plan::find($plan);
            if ($planModel) {
                return $planModel->getConfig();
            }
        }

        // 尝试通过代码从数据库获取
        $planModel = Plan::findByCode((string) $plan);
        if ($planModel) {
            return $planModel->getConfig();
        }

        // 兼容旧代码的硬编码配置
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
     * 获取关联的计划
     */
    public function planModel(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
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
     *
     * @param Member $member 会员
     * @param string|int|Plan $plan 计划代码、ID 或 Plan 模型
     * @param int|null $durationDays 时长（天），null 则使用计划默认时长
     */
    public static function createForMember(
        Member $member,
        string|int|Plan $plan,
        ?int $durationDays = null
    ): self {
        // 取消会员当前有效订阅
        self::where('member_id', $member->id)
            ->where('status', self::STATUS_ACTIVE)
            ->update(['status' => self::STATUS_CANCELLED]);

        // 解析计划
        $planModel = null;
        $planCode = null;
        $planId = null;

        if ($plan instanceof Plan) {
            $planModel = $plan;
            $planCode = $plan->code;
            $planId = $plan->id;
        } elseif (is_numeric($plan)) {
            $planModel = Plan::find($plan);
            $planCode = $planModel?->code ?? self::PLAN_FREE;
            $planId = $planModel?->id;
        } else {
            $planModel = Plan::findByCode($plan);
            $planCode = $plan;
            $planId = $planModel?->id;
        }

        // 如果找不到有效计划，尝试获取默认计划
        if (!$planModel) {
            $planModel = Plan::getDefault();
            if ($planModel) {
                $planCode = $planModel->code;
                $planId = $planModel->id;
            }
        }

        // 如果仍然没有有效计划，抛出异常避免创建孤立记录
        if (!$planId) {
            throw new \InvalidArgumentException(
                "无法创建订阅：找不到有效的计划。请确保数据库中存在计划记录，或指定有效的计划 ID/代码。提供的计划: {$planCode}"
            );
        }

        // 获取配置
        $config = $planModel->getConfig();

        // 确定时长
        $duration = $durationDays ?? $planModel->duration_days;

        // 计算过期时间
        // duration_days = 0 表示永久有效，不设置过期时间
        // duration_days > 0 表示有限期，设置过期时间
        $expiresAt = null;
        if ($duration > 0) {
            $expiresAt = now()->addDays($duration);
        }

        $subscription = self::create([
            'member_id' => $member->id,
            'plan_id' => $planId,
            'plan' => $planCode,
            'status' => self::STATUS_ACTIVE,
            'starts_at' => now(),
            'expires_at' => $expiresAt,
            'daily_print_limit' => $config['daily_print_limit'],
            'filters_enabled' => $config['filters_enabled'],
            'custom_template_enabled' => $config['custom_template_enabled'],
            'api_access_enabled' => $config['api_access_enabled'],
        ]);

        // 更新会员的计划信息（subscription_expiry 已移至 subscriptions 表）
        $member->update([
            'plan_id' => $planId,
            'plan' => $planCode,
        ]);

        return $subscription;
    }

    /**
     * 获取计划名称
     */
    public function getPlanNameAttribute(): string
    {
        if ($this->planModel) {
            return $this->planModel->name;
        }

        return match ($this->plan) {
            self::PLAN_FREE => '免费版',
            self::PLAN_PRO => '专业版',
            self::PLAN_ENTERPRISE => '企业版',
            default => $this->plan,
        };
    }
}
