<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User - 后台管理员用户模型
 * 
 * 仅用于 Filament 后台管理面板登录，
 * 前端客户端用户请使用 Member 模型。
 */
class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    /**
     * 可批量赋值的属性
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * 判断用户是否可以访问 Filament 后台
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // 所有管理员都可以访问后台
        return true;
    }
}
