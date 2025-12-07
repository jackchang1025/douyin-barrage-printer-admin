<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * 清理 users 表，移除前端用户相关字段
     * users 表仅用于后台管理员
     */
    public function up(): void
    {
        // 检查表是否存在这些字段，如果存在则删除
        Schema::table('users', function (Blueprint $table) {
            // 先删除唯一索引
            if (Schema::hasIndex('users', 'users_phone_unique')) {
                $table->dropUnique('users_phone_unique');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $columns = Schema::getColumnListing('users');
            
            // 移除会员相关字段
            $columnsToRemove = [
                'country_code',
                'phone',
                'plan',
                'subscription_expiry',
                'is_admin',
                'phone_verified_at',
            ];

            foreach ($columnsToRemove as $column) {
                if (in_array($column, $columns)) {
                    $table->dropColumn($column);
                }
            }
        });

        // 确保 email 字段是必填的
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('country_code', 10)->nullable()->after('name')->comment('国家区号');
            $table->string('phone', 20)->nullable()->after('country_code')->comment('手机号码');
            $table->string('email')->nullable()->change();
            $table->string('plan')->default('free')->after('password')->comment('订阅计划');
            $table->timestamp('subscription_expiry')->nullable()->after('plan')->comment('订阅过期时间');
            $table->boolean('is_admin')->default(false)->after('subscription_expiry')->comment('是否是管理员');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at')->comment('手机验证时间');
            
            $table->unique(['country_code', 'phone'], 'users_phone_unique');
        });
    }
};

