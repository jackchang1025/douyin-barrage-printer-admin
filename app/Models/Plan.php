<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'price',
        'duration_days',
        'daily_print_limit',
        'filters_enabled',
        'custom_template_enabled',
        'api_access_enabled',
        'color',
        'sort_order',
        'is_active',
        'is_default',
        'features',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'filters_enabled' => 'boolean',
            'custom_template_enabled' => 'boolean',
            'api_access_enabled' => 'boolean',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'features' => 'array',
        ];
    }

    /**
     * 预定义的计划代码
     */
    const CODE_FREE = 'free';
    const CODE_PRO = 'pro';
    const CODE_ENTERPRISE = 'enterprise';

    /**
     * 获取该计划的所有订阅
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * 获取使用该计划的会员
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /**
     * 作用域：启用的计划
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * 作用域：按排序顺序
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * 获取默认计划
     */
    public static function getDefault(): ?self
    {
        return self::where('is_default', true)->where('is_active', true)->first();
    }

    /**
     * 通过代码获取计划
     */
    public static function findByCode(string $code): ?self
    {
        return self::where('code', $code)->first();
    }

    /**
     * 获取所有启用的计划选项（用于下拉框）
     */
    public static function getActiveOptions(): array
    {
        return self::active()
            ->ordered()
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * 获取计划配置（兼容旧代码）
     */
    public function getConfig(): array
    {
        return [
            'name' => $this->name,
            'daily_print_limit' => $this->daily_print_limit,
            'filters_enabled' => $this->filters_enabled,
            'custom_template_enabled' => $this->custom_template_enabled,
            'api_access_enabled' => $this->api_access_enabled,
        ];
    }

    /**
     * 获取打印限制显示文本
     */
    public function getPrintLimitTextAttribute(): string
    {
        return $this->daily_print_limit === -1 ? '无限制' : (string) $this->daily_print_limit;
    }

    /**
     * 获取价格显示文本
     */
    public function getPriceTextAttribute(): string
    {
        return $this->price > 0 ? '¥' . number_format($this->price, 2) : '免费';
    }

    /**
     * 获取时长显示文本
     */
    public function getDurationTextAttribute(): string
    {
        if ($this->duration_days === 0) {
            return '永久';
        }
        if ($this->duration_days >= 365) {
            $years = floor($this->duration_days / 365);
            return $years . ' 年';
        }
        if ($this->duration_days >= 30) {
            $months = floor($this->duration_days / 30);
            return $months . ' 个月';
        }
        return $this->duration_days . ' 天';
    }

    /**
     * 检查是否为免费计划
     */
    public function isFree(): bool
    {
        return $this->price == 0;
    }
}

