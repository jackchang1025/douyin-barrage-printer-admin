<?php

namespace Database\Seeders;

use App\Models\User;
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
        // 创建管理员用户
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => '系统管理员',
                'email' => 'admin@example.com',
                'country_code' => '+86',
                'phone' => '13800000000',
                'password' => Hash::make('admin123'),
                'is_admin' => true,
                'plan' => 'enterprise',
                'subscription_expiry' => now()->addYears(10),
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        // 为管理员创建企业版订阅
        if ($admin->wasRecentlyCreated) {
            Subscription::create([
                'user_id' => $admin->id,
                'plan' => Subscription::PLAN_ENTERPRISE,
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => now(),
                'expires_at' => now()->addYears(10),
                'daily_print_limit' => -1,
                'filters_enabled' => true,
                'custom_template_enabled' => true,
                'api_access_enabled' => true,
            ]);

            $this->command->info('管理员账户已创建:');
            $this->command->info('  邮箱: admin@example.com');
            $this->command->info('  密码: admin123');
        }

        // 创建测试用户
        $testUser = User::firstOrCreate(
            ['phone' => '13900000000', 'country_code' => '+86'],
            [
                'name' => '测试用户',
                'email' => 'test@example.com',
                'country_code' => '+86',
                'phone' => '13900000000',
                'password' => Hash::make('123456'),
                'is_admin' => false,
                'plan' => 'pro',
                'subscription_expiry' => now()->addDays(30),
                'phone_verified_at' => now(),
            ]
        );

        if ($testUser->wasRecentlyCreated) {
            Subscription::create([
                'user_id' => $testUser->id,
                'plan' => Subscription::PLAN_PRO,
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => now(),
                'expires_at' => now()->addDays(30),
                'daily_print_limit' => 100,
                'filters_enabled' => true,
                'custom_template_enabled' => true,
                'api_access_enabled' => false,
            ]);

            $this->command->info('测试用户已创建:');
            $this->command->info('  手机: +86 13900000000');
            $this->command->info('  密码: 123456');
        }
    }
}
