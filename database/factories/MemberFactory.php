<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nickname' => '用户' . $this->faker->unique()->numerify('####'),
            'country_code' => '+86',
            'phone' => $this->faker->unique()->numerify('138########'),
            'password' => Hash::make('password'),
            'avatar' => null,
            'plan' => 'free',
            'phone_verified_at' => now(),
            'status' => Member::STATUS_ACTIVE,
            'last_login_at' => null,
            'last_login_ip' => null,
        ];
    }

    /**
     * 未验证手机的会员
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_verified_at' => null,
        ]);
    }

    /**
     * Pro 会员（自动创建订阅记录）
     */
    public function pro(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => 'pro',
        ])->afterCreating(function (Member $member) {
            $plan = Plan::findByCode('pro');
            Subscription::create([
                'member_id' => $member->id,
                'plan_id' => $plan?->id,
                'plan' => 'pro',
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => now()->addDays(30),
                'daily_print_limit' => $plan?->daily_print_limit ?? 100,
                'filters_enabled' => $plan?->filters_enabled ?? true,
                'custom_template_enabled' => $plan?->custom_template_enabled ?? true,
                'api_access_enabled' => $plan?->api_access_enabled ?? false,
            ]);
        });
    }

    /**
     * Enterprise 会员（自动创建订阅记录）
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => 'enterprise',
        ])->afterCreating(function (Member $member) {
            $plan = Plan::findByCode('enterprise');
            Subscription::create([
                'member_id' => $member->id,
                'plan_id' => $plan?->id,
                'plan' => 'enterprise',
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
                'daily_print_limit' => $plan?->daily_print_limit ?? -1,
                'filters_enabled' => $plan?->filters_enabled ?? true,
                'custom_template_enabled' => $plan?->custom_template_enabled ?? true,
                'api_access_enabled' => $plan?->api_access_enabled ?? true,
            ]);
        });
    }

    /**
     * 订阅已过期的会员（自动创建过期的订阅记录）
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => 'pro',
        ])->afterCreating(function (Member $member) {
            $plan = Plan::findByCode('pro');
            Subscription::create([
                'member_id' => $member->id,
                'plan_id' => $plan?->id,
                'plan' => 'pro',
                'status' => 'expired',
                'starts_at' => now()->subDays(37),
                'expires_at' => now()->subDays(7),
                'daily_print_limit' => $plan?->daily_print_limit ?? 100,
                'filters_enabled' => $plan?->filters_enabled ?? true,
                'custom_template_enabled' => $plan?->custom_template_enabled ?? true,
                'api_access_enabled' => $plan?->api_access_enabled ?? false,
            ]);
        });
    }

    /**
     * 带订阅记录的会员（通用方法）
     */
    public function withSubscription(?string $planCode = 'free', ?int $durationDays = null): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => $planCode,
        ])->afterCreating(function (Member $member) use ($planCode, $durationDays) {
            $plan = Plan::findByCode($planCode ?? 'free');
            $duration = $durationDays ?? ($plan?->duration_days ?? 0);
            
            Subscription::create([
                'member_id' => $member->id,
                'plan_id' => $plan?->id,
                'plan' => $planCode ?? 'free',
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => $duration > 0 ? now()->addDays($duration) : null,
                'daily_print_limit' => $plan?->daily_print_limit ?? 10,
                'filters_enabled' => $plan?->filters_enabled ?? false,
                'custom_template_enabled' => $plan?->custom_template_enabled ?? false,
                'api_access_enabled' => $plan?->api_access_enabled ?? false,
            ]);
        });
    }

    /**
     * 禁用的会员
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Member::STATUS_DISABLED,
        ]);
    }

    /**
     * 封禁的会员
     */
    public function banned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Member::STATUS_BANNED,
        ]);
    }
}

