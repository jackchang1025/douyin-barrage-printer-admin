<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'plan',
        'subscription_expiry',
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
            'subscription_expiry' => 'datetime',
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
     * 获取订阅剩余天数
     */
    public function getSubscriptionDaysRemainingAttribute(): int
    {
        if (!$this->subscription_expiry) {
            return 0;
        }

        $days = now()->diffInDays($this->subscription_expiry, false);
        return max(0, (int) $days);
    }

    /**
     * 检查订阅是否有效
     */
    public function isSubscriptionActive(): bool
    {
        if ($this->plan === 'free') {
            return true;
        }

        return $this->subscription_expiry && $this->subscription_expiry->isFuture();
    }

    /**
     * 获取订阅功能
     */
    public function getSubscriptionFeatures(): array
    {
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

