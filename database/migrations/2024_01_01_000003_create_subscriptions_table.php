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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('plan')->comment('订阅计划: free, pro, enterprise');
            $table->string('status')->default('active')->comment('状态: active, cancelled, expired');
            $table->timestamp('starts_at')->comment('开始时间');
            $table->timestamp('expires_at')->nullable()->comment('过期时间');
            $table->integer('daily_print_limit')->default(10)->comment('每日打印限制，-1表示无限制');
            $table->boolean('filters_enabled')->default(false)->comment('是否启用过滤器');
            $table->boolean('custom_template_enabled')->default(false)->comment('是否启用自定义模板');
            $table->boolean('api_access_enabled')->default(false)->comment('是否启用API访问');
            $table->json('metadata')->nullable()->comment('其他元数据');
            $table->timestamps();
            
            // 索引
            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};

