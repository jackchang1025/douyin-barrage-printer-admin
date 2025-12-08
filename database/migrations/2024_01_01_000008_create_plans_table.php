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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('计划标识，如 free, pro, enterprise');
            $table->string('name')->comment('计划显示名称');
            $table->text('description')->nullable()->comment('计划描述');
            $table->decimal('price', 10, 2)->default(0)->comment('价格');
            $table->integer('duration_days')->default(30)->comment('默认时长（天），0表示永久');
            $table->integer('daily_print_limit')->default(10)->comment('每日打印限制，-1表示无限制');
            $table->boolean('filters_enabled')->default(false)->comment('是否启用过滤器');
            $table->boolean('custom_template_enabled')->default(false)->comment('是否启用自定义模板');
            $table->boolean('api_access_enabled')->default(false)->comment('是否启用API访问');
            $table->string('color')->default('gray')->comment('显示颜色：gray, success, warning, danger, info');
            $table->integer('sort_order')->default(0)->comment('排序顺序');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->boolean('is_default')->default(false)->comment('是否默认计划（新用户自动获得）');
            $table->json('features')->nullable()->comment('额外功能配置');
            $table->timestamps();

            // 索引
            $table->index('is_active');
            $table->index('sort_order');
        });

        // 为 subscriptions 表添加 plan_id 外键
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('member_id')->constrained('plans')->nullOnDelete();
        });

        // 为 members 表添加 plan_id 外键
        Schema::table('members', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('avatar')->constrained('plans')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn('plan_id');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn('plan_id');
        });

        Schema::dropIfExists('plans');
    }
};

