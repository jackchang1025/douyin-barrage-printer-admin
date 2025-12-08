<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'code' => Plan::CODE_FREE,
                'name' => '免费版',
                'description' => '适合个人用户体验，包含基础功能',
                'price' => 0,
                'duration_days' => 0, // 永久
                'daily_print_limit' => 10,
                'filters_enabled' => false,
                'custom_template_enabled' => false,
                'api_access_enabled' => false,
                'color' => 'gray',
                'sort_order' => 1,
                'is_active' => true,
                'is_default' => true,
                'features' => [
                    '每日最多打印 10 条弹幕',
                    '基础弹幕显示',
                    '标准打印模板',
                ],
            ],
            [
                'code' => Plan::CODE_PRO,
                'name' => '专业版',
                'description' => '适合主播和内容创作者，解锁更多功能',
                'price' => 29.00,
                'duration_days' => 30,
                'daily_print_limit' => 100,
                'filters_enabled' => true,
                'custom_template_enabled' => true,
                'api_access_enabled' => false,
                'color' => 'success',
                'sort_order' => 2,
                'is_active' => true,
                'is_default' => false,
                'features' => [
                    '每日最多打印 100 条弹幕',
                    '弹幕过滤器',
                    '自定义打印模板',
                    '优先技术支持',
                ],
            ],
            [
                'code' => Plan::CODE_ENTERPRISE,
                'name' => '企业版',
                'description' => '适合企业和团队使用，无限制功能',
                'price' => 99.00,
                'duration_days' => 30,
                'daily_print_limit' => -1, // 无限制
                'filters_enabled' => true,
                'custom_template_enabled' => true,
                'api_access_enabled' => true,
                'color' => 'warning',
                'sort_order' => 3,
                'is_active' => true,
                'is_default' => false,
                'features' => [
                    '无限弹幕打印',
                    '高级弹幕过滤器',
                    '自定义打印模板',
                    'API 访问权限',
                    '专属客服支持',
                    '数据分析报告',
                ],
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['code' => $planData['code']],
                $planData
            );
        }

        $this->command->info('默认计划已创建/更新');
    }
}

