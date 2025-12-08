<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Member - 前端会员用户模型
 * 
 * 用于前端客户端（Electron）的用户注册、登录和订阅管理
 */
class Member extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * 模型对应的数据表
     */
    protected $table = 'members';

    /**
     * 可批量赋值的属性
     *
     * @var list<string>
     */
    protected $fillable = [
        'nickname',
        'country_code',
        'phone',
        'password',
        'avatar',
        'plan_id',
        'plan',
        'phone_verified_at',
        'status',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * 应该隐藏的属性
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * 属性类型转换
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * 状态常量
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_DISABLED = 'disabled';
    const STATUS_BANNED = 'banned';

    /**
     * 获取会员的订阅记录
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'member_id');
    }

    /**
     * 获取会员的当前计划
     */
    public function planModel(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    /**
     * 获取会员当前有效的订阅
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class, 'member_id')
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest();
    }

    /**
     * 检查会员是否有有效订阅
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    /**
     * 获取完整手机号
     */
    public function getFullPhoneAttribute(): string
    {
        return $this->country_code . $this->phone;
    }

    /**
     * 获取订阅过期时间（从最新订阅记录获取）
     * 
     * 这是一个虚拟属性，数据来源于 subscriptions 表
     * 保持向后兼容，使用方式不变：$member->subscription_expiry
     * 
     * 注意：使用 $this->latestSubscription（属性访问）是正确的 Laravel 写法，
     * Laravel 的 __get() 会自动处理关联加载和缓存。
     */
    public function getSubscriptionExpiryAttribute(): ?Carbon
    {
        return $this->latestSubscription?->expires_at;
    }

    /**
     * 获取会员最新的订阅记录
     */
    public function latestSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class, 'member_id')
            ->latest('created_at');
    }

    /**
     * 获取订阅剩余天数
     */
    public function getSubscriptionDaysRemainingAttribute(): int
    {
        $expiry = $this->subscription_expiry;
        if (!$expiry) {
            return 0;
        }

        $days = now()->diffInDays($expiry, false);
        return max(0, (int) $days);
    }

    /**
     * 检查订阅是否有效
     * 
     * 逻辑：
     * 1. 如果没有过期时间（subscription_expiry 为 null），视为永久有效
     * 2. 如果有过期时间，检查是否未过期
     */
    public function isSubscriptionActive(): bool
    {
        $expiry = $this->subscription_expiry;

        // 没有设置过期时间，视为永久有效
        if ($expiry === null) {
            return true;
        }

        // 有过期时间，检查是否未过期
        return $expiry->isFuture();
    }

    /**
     * 获取订阅功能
     */
    public function getSubscriptionFeatures(): array
    {
        // 优先从关联的 Plan 模型获取
        if ($this->planModel) {
            return [
                'daily_print_limit' => $this->planModel->daily_print_limit,
                'filters' => $this->planModel->filters_enabled,
                'custom_template' => $this->planModel->custom_template_enabled,
                'api_access' => $this->planModel->api_access_enabled,
            ];
        }

        // 兼容旧代码
        $features = [
            'daily_print_limit' => 10,
            'filters' => false,
            'custom_template' => false,
            'api_access' => false,
        ];

        switch ($this->plan) {
            case 'pro':
                $features = [
                    'daily_print_limit' => 100,
                    'filters' => true,
                    'custom_template' => true,
                    'api_access' => false,
                ];
                break;
            case 'enterprise':
                $features = [
                    'daily_print_limit' => -1, // 无限制
                    'filters' => true,
                    'custom_template' => true,
                    'api_access' => true,
                ];
                break;
        }

        return $features;
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
            'free' => '免费版',
            'pro' => '专业版',
            'enterprise' => '企业版',
            default => $this->plan ?? '免费版',
        };
    }

    /**
     * 检查账号是否可用
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * 记录登录信息
     */
    public function recordLogin(?string $ip = null): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }
}
