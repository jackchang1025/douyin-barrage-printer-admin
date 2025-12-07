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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('nickname')->nullable()->comment('会员昵称');
            $table->string('country_code', 10)->comment('国家区号');
            $table->string('phone', 20)->comment('手机号码');
            $table->string('password')->comment('密码');
            $table->string('avatar')->nullable()->comment('头像URL');
            $table->string('plan')->default('free')->comment('订阅计划: free, pro, enterprise');
            $table->timestamp('subscription_expiry')->nullable()->comment('订阅过期时间');
            $table->timestamp('phone_verified_at')->nullable()->comment('手机验证时间');
            $table->string('status')->default('active')->comment('状态: active, disabled, banned');
            $table->timestamp('last_login_at')->nullable()->comment('最后登录时间');
            $table->string('last_login_ip', 45)->nullable()->comment('最后登录IP');
            $table->rememberToken();
            $table->timestamps();

            // 添加唯一索引
            $table->unique(['country_code', 'phone'], 'members_phone_unique');
            
            // 添加普通索引
            $table->index('status');
            $table->index('plan');
            $table->index('subscription_expiry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};

