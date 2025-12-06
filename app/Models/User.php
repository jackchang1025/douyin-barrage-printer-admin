<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'country_code',
        'phone',
        'plan',
        'subscription_expiry',
        'is_admin',
        'phone_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'subscription_expiry' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * 获取用户的订阅记录
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * 获取用户当前有效的订阅
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest();
    }

    /**
     * 检查用户是否有有效订阅
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
     * 判断用户是否可以访问 Filament 后台
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }
}
