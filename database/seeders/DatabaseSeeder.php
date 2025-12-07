<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Member;
use App\Models\Subscription;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 创建后台管理员
        $admin = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => '系统管理员',
                'email' => 'admin@admin.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        if ($admin->wasRecentlyCreated) {
            $this->command->info('✅ 后台管理员已创建:');
            $this->command->info('   邮箱: admin@admin.com');
            $this->command->info('   密码: password');
            $this->command->warn('   请登录后立即修改密码！');
        }

        // 创建测试会员
        $testMember = Member::firstOrCreate(
            ['phone' => '13900000000', 'country_code' => '+86'],
            [
                'nickname' => '测试会员',
                'country_code' => '+86',
                'phone' => '13900000000',
                'password' => Hash::make('123456'),
                'plan' => 'pro',
                'subscription_expiry' => now()->addDays(30),
                'phone_verified_at' => now(),
                'status' => Member::STATUS_ACTIVE,
            ]
        );

        if ($testMember->wasRecentlyCreated) {
            Subscription::create([
                'member_id' => $testMember->id,
                'plan' => Subscription::PLAN_PRO,
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => now(),
                'expires_at' => now()->addDays(30),
                'daily_print_limit' => 100,
                'filters_enabled' => true,
                'custom_template_enabled' => true,
                'api_access_enabled' => false,
            ]);

            $this->command->info('✅ 测试会员已创建:');
            $this->command->info('   手机: +86 13900000000');
            $this->command->info('   密码: 123456');
        }
    }
}
