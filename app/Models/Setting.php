<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * 系统设置模型
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property string $type
 * @property string $group
 * @property string|null $label
 * @property string|null $description
 * @property int $sort_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'sort_order',
    ];

    /**
     * 设置分组常量
     */
    public const GROUP_GENERAL = 'general';
    public const GROUP_CONTACT = 'contact';
    public const GROUP_SUBSCRIPTION = 'subscription';

    /**
     * 值类型常量
     */
    public const TYPE_STRING = 'string';
    public const TYPE_TEXT = 'text';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_JSON = 'json';
    public const TYPE_IMAGE = 'image';

    /**
     * 缓存键前缀
     */
    protected const CACHE_PREFIX = 'setting:';
    protected const CACHE_TTL = 3600; // 1小时

    /**
     * 获取设置值
     *
     * @param string $key 设置键名
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = self::CACHE_PREFIX . $key;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }

            return self::castValue($setting->value, $setting->type);
        });
    }

    /**
     * 设置值
     *
     * @param string $key 设置键名
     * @param mixed $value 设置值
     * @param array $options 可选参数 (type, group, label, description)
     * @return self
     */
    public static function set(string $key, mixed $value, array $options = []): self
    {
        $type = $options['type'] ?? self::TYPE_STRING;

        // 处理值的序列化
        $storedValue = self::serializeValue($value, $type);

        $setting = self::updateOrCreate(
            ['key' => $key],
            array_merge([
                'value' => $storedValue,
                'type' => $type,
            ], $options)
        );

        // 清除缓存
        Cache::forget(self::CACHE_PREFIX . $key);

        return $setting;
    }

    /**
     * 获取指定分组的所有设置
     *
     * @param string $group
     * @return array
     */
    public static function getGroup(string $group): array
    {
        $settings = self::where('group', $group)
            ->orderBy('sort_order')
            ->get();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = self::castValue($setting->value, $setting->type);
        }

        return $result;
    }

    /**
     * 获取联系方式设置
     *
     * @return array
     */
    public static function getContactSettings(): array
    {
        return [
            'contact_phone' => self::get('contact_phone'),
            'contact_wechat_qrcode' => self::get('contact_wechat_qrcode'),
            'contact_qq_qrcode' => self::get('contact_qq_qrcode'),
            'contact_description' => self::get('contact_description'),
            'renewal_guide' => self::get('renewal_guide'),
        ];
    }

    /**
     * 根据类型转换值
     *
     * @param string|null $value
     * @param string $type
     * @return mixed
     */
    protected static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            self::TYPE_BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            self::TYPE_INTEGER => (int) $value,
            self::TYPE_JSON => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * 序列化值用于存储
     *
     * @param mixed $value
     * @param string $type
     * @return string|null
     */
    protected static function serializeValue(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            self::TYPE_BOOLEAN => $value ? '1' : '0',
            self::TYPE_JSON => is_string($value) ? $value : json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * 清除所有设置缓存
     */
    public static function clearCache(): void
    {
        $settings = self::all();
        foreach ($settings as $setting) {
            Cache::forget(self::CACHE_PREFIX . $setting->key);
        }
    }
}

