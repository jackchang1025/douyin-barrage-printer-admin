<?php

namespace Database\Factories;

use App\Models\Member;
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
            'subscription_expiry' => null,
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
     * Pro 会员
     */
    public function pro(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => 'pro',
            'subscription_expiry' => now()->addDays(30),
        ]);
    }

    /**
     * Enterprise 会员
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => 'enterprise',
            'subscription_expiry' => now()->addYear(),
        ]);
    }

    /**
     * 订阅已过期的会员
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => 'pro',
            'subscription_expiry' => now()->subDays(7),
        ]);
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

