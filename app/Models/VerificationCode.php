<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class VerificationCode extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'country_code',
        'phone',
        'code',
        'type',
        'expires_at',
        'is_used',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_used' => 'boolean',
        ];
    }

    /**
     * 验证码类型
     */
    const TYPE_LOGIN = 'login';
    const TYPE_REGISTER = 'register';
    const TYPE_RESET = 'reset';

    /**
     * 生成随机验证码
     */
    public static function generateCode(int $length = 6): string
    {
        return str_pad((string) random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    /**
     * 创建验证码
     */
    public static function createCode(
        string $countryCode, 
        string $phone, 
        string $type = self::TYPE_LOGIN,
        int $expiresInMinutes = 5
    ): self {
        // 使已存在的同类型验证码失效
        self::where('country_code', $countryCode)
            ->where('phone', $phone)
            ->where('type', $type)
            ->where('is_used', false)
            ->update(['is_used' => true]);

        return self::create([
            'country_code' => $countryCode,
            'phone' => $phone,
            'code' => self::generateCode(),
            'type' => $type,
            'expires_at' => now()->addMinutes($expiresInMinutes),
            'is_used' => false,
        ]);
    }

    /**
     * 验证验证码
     */
    public static function verify(
        string $countryCode, 
        string $phone, 
        string $code, 
        string $type = self::TYPE_LOGIN
    ): bool {
        $verification = self::where('country_code', $countryCode)
            ->where('phone', $phone)
            ->where('code', $code)
            ->where('type', $type)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->first();

        if ($verification) {
            $verification->update(['is_used' => true]);
            return true;
        }

        return false;
    }

    /**
     * 检查是否可以发送验证码（防止频繁发送）
     */
    public static function canSend(
        string $countryCode, 
        string $phone, 
        int $intervalSeconds = 60
    ): bool {
        $latestCode = self::where('country_code', $countryCode)
            ->where('phone', $phone)
            ->where('is_used', false)
            ->latest()
            ->first();

        if (!$latestCode) {
            return true;
        }

        return $latestCode->created_at->diffInSeconds(now()) >= $intervalSeconds;
    }

    /**
     * 作用域：未过期的验证码
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('is_used', false)
            ->where('expires_at', '>', now());
    }
}

