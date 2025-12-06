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
        Schema::create('verification_codes', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 10)->comment('国家区号');
            $table->string('phone', 20)->comment('手机号码');
            $table->string('code', 10)->comment('验证码');
            $table->string('type')->default('login')->comment('验证码类型: login, register, reset');
            $table->timestamp('expires_at')->comment('过期时间');
            $table->boolean('is_used')->default(false)->comment('是否已使用');
            $table->timestamps();
            
            // 索引
            $table->index(['country_code', 'phone', 'code']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_codes');
    }
};

