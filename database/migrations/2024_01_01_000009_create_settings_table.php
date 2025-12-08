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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('设置键名');
            $table->text('value')->nullable()->comment('设置值');
            $table->string('type')->default('string')->comment('值类型: string, text, boolean, integer, json, image');
            $table->string('group')->default('general')->comment('设置分组');
            $table->string('label')->nullable()->comment('显示标签');
            $table->text('description')->nullable()->comment('描述说明');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->timestamps();

            $table->index('group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

