<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('country_code', 10)->nullable()->after('name')->comment('国家区号');
            $table->string('phone', 20)->nullable()->after('country_code')->comment('手机号码');
            $table->string('email')->nullable()->change(); // 允许 email 为空
            $table->string('plan')->default('free')->after('password')->comment('订阅计划: free, pro, enterprise');
            $table->timestamp('subscription_expiry')->nullable()->after('plan')->comment('订阅过期时间');
            $table->boolean('is_admin')->default(false)->after('subscription_expiry')->comment('是否是管理员');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at')->comment('手机验证时间');
            
            // 添加唯一索引
            $table->unique(['country_code', 'phone'], 'users_phone_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_phone_unique');
            $table->dropColumn([
                'country_code',
                'phone',
                'plan',
                'subscription_expiry',
                'is_admin',
                'phone_verified_at',
            ]);
        });
    }
};

