<?php

namespace App\Filament\Admin\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SiteSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static string $view = 'filament.admin.pages.site-settings';

    protected static ?string $navigationLabel = '网站设置';

    protected static ?string $title = '网站设置';

    protected static ?string $navigationGroup = '系统设置';

    protected static ?int $navigationSort = 99;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'site_name' => Setting::get('site_name', '抖音弹幕打印系统'),
            'site_slogan' => Setting::get('site_slogan', '专业的直播弹幕打印解决方案'),
            'site_description' => Setting::get('site_description', '实时捕获抖音直播间弹幕，支持自定义打印模板，让您的直播互动更加精彩'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('网站基本信息')
                    ->description('设置首页显示的网站基本信息')
                    ->schema([
                        Forms\Components\TextInput::make('site_name')
                            ->label('网站名称')
                            ->placeholder('请输入网站名称')
                            ->required()
                            ->maxLength(50)
                            ->helperText('网站名称，显示在首页标题和页脚'),

                        Forms\Components\TextInput::make('site_slogan')
                            ->label('网站标语')
                            ->placeholder('请输入网站标语')
                            ->maxLength(100)
                            ->helperText('网站标语，显示在首页副标题'),

                        Forms\Components\Textarea::make('site_description')
                            ->label('网站描述')
                            ->placeholder('请输入网站描述')
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('网站描述，显示在首页介绍区域'),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // 保存每个设置项
        Setting::set('site_name', $data['site_name'] ?? '抖音弹幕打印系统', [
            'type' => Setting::TYPE_STRING,
            'group' => Setting::GROUP_GENERAL,
            'label' => '网站名称',
        ]);

        Setting::set('site_slogan', $data['site_slogan'] ?? '', [
            'type' => Setting::TYPE_STRING,
            'group' => Setting::GROUP_GENERAL,
            'label' => '网站标语',
        ]);

        Setting::set('site_description', $data['site_description'] ?? '', [
            'type' => Setting::TYPE_TEXT,
            'group' => Setting::GROUP_GENERAL,
            'label' => '网站描述',
        ]);

        Notification::make()
            ->title('设置已保存')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('保存设置')
                ->submit('save'),
        ];
    }
}
