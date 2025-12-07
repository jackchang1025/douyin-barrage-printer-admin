<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * 创建默认管理员账号
     */
    public function run(): void
    {
        // 检查是否已存在管理员
        if (User::count() > 0) {
            $this->command->info('管理员账号已存在，跳过创建');
            return;
        }

        User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $this->command->info('✅ 默认管理员创建成功');
        $this->command->info('   邮箱: admin@admin.com');
        $this->command->info('   密码: password');
        $this->command->warn('   请登录后立即修改密码！');
    }
}
