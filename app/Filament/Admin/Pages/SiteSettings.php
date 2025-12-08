<?php

namespace App\Filament\Admin\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

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
        // 获取 hero_media JSON 数据并转换为数组格式
        $heroMedia = Setting::get('hero_media', []);
        if (is_string($heroMedia)) {
            $heroMedia = json_decode($heroMedia, true) ?? [];
        }

        $this->form->fill([
            // 基本信息
            'site_name' => Setting::get('site_name', '抖音弹幕打印系统'),
            'site_slogan' => Setting::get('site_slogan', '专业的直播弹幕打印解决方案'),
            'site_description' => Setting::get('site_description', '实时捕获抖音直播间弹幕，支持自定义打印模板，让您的直播互动更加精彩'),
            // 媒体设置
            'site_favicon' => $this->pathToArray(Setting::get('site_favicon')),
            'site_logo' => $this->pathToArray(Setting::get('site_logo')),
            'hero_media' => $heroMedia,
            'hero_video_url' => Setting::get('hero_video_url'),
            // SEO 设置
            'seo_keywords' => Setting::get('seo_keywords', '抖音弹幕打印,直播弹幕,弹幕打印机,抖音直播工具'),
            'seo_author' => Setting::get('seo_author'),
            'og_image' => $this->pathToArray(Setting::get('og_image')),
        ]);
    }

    /**
     * 将路径字符串转换为 FileUpload 组件需要的数组格式
     */
    private function pathToArray(?string $path): array
    {
        if (empty($path)) {
            return [];
        }
        return [$path];
    }

    /**
     * 从数组中提取路径字符串
     */
    private function arrayToPath(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        if (is_array($value)) {
            return reset($value) ?: null;
        }
        return $value;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('设置')
                    ->tabs([
                        // 基本信息标签页
                        Forms\Components\Tabs\Tab::make('基本信息')
                            ->icon('heroicon-o-information-circle')
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
                            ]),

                        // 媒体设置标签页
                        Forms\Components\Tabs\Tab::make('媒体设置')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Forms\Components\Section::make('网站图标与Logo')
                                    ->description('设置网站的图标和Logo')
                                    ->schema([
                                        Forms\Components\FileUpload::make('site_favicon')
                                            ->label('网站图标 (Favicon)')
                                            ->image()
                                            ->disk('public')
                                            ->directory('site')
                                            ->acceptedFileTypes(['image/x-icon', 'image/png', 'image/svg+xml', 'image/ico', 'image/vnd.microsoft.icon'])
                                            ->maxSize(512)
                                            ->helperText('推荐尺寸: 32x32 或 64x64 像素，支持 ICO/PNG/SVG 格式'),

                                        Forms\Components\FileUpload::make('site_logo')
                                            ->label('网站 Logo')
                                            ->image()
                                            ->disk('public')
                                            ->directory('site')
                                            ->maxSize(2048)
                                            ->helperText('网站Logo，显示在导航栏'),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('首页展示媒体')
                                    ->description('上传首页展示的图片、GIF或视频（支持多文件）')
                                    ->schema([
                                        Forms\Components\FileUpload::make('hero_media')
                                            ->label('展示媒体（图片/GIF）')
                                            ->multiple()
                                            ->reorderable()
                                            ->disk('public')
                                            ->directory('hero')
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                                            ->maxSize(10240) // 10MB
                                            ->maxFiles(10)
                                            ->helperText('支持 JPG/PNG/GIF/WebP 格式，最多上传10个文件，每个最大10MB'),

                                        Forms\Components\TextInput::make('hero_video_url')
                                            ->label('视频链接')
                                            ->url()
                                            ->placeholder('https://example.com/video.mp4')
                                            ->helperText('外部视频链接（如腾讯云/阿里云 CDN），支持 MP4/WebM 格式'),
                                    ])
                                    ->columns(1),
                            ]),

                        // SEO 设置标签页
                        Forms\Components\Tabs\Tab::make('SEO 设置')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Forms\Components\Section::make('搜索引擎优化')
                                    ->description('优化网站在搜索引擎中的展示')
                                    ->schema([
                                        Forms\Components\TextInput::make('seo_keywords')
                                            ->label('关键词')
                                            ->placeholder('关键词1,关键词2,关键词3')
                                            ->maxLength(200)
                                            ->helperText('多个关键词用英文逗号分隔'),

                                        Forms\Components\TextInput::make('seo_author')
                                            ->label('作者')
                                            ->placeholder('网站作者或公司名称')
                                            ->maxLength(100),

                                        Forms\Components\FileUpload::make('og_image')
                                            ->label('社交分享图片 (OG Image)')
                                            ->image()
                                            ->disk('public')
                                            ->directory('site')
                                            ->maxSize(2048)
                                            ->helperText('推荐尺寸: 1200x630 像素，用于社交媒体分享时的预览图'),
                                    ])
                                    ->columns(1),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // 保存基本信息
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

        // 保存媒体设置
        Setting::set('site_favicon', $this->arrayToPath($data['site_favicon']), [
            'type' => Setting::TYPE_IMAGE,
            'group' => Setting::GROUP_GENERAL,
            'label' => '网站图标',
        ]);

        Setting::set('site_logo', $this->arrayToPath($data['site_logo']), [
            'type' => Setting::TYPE_IMAGE,
            'group' => Setting::GROUP_GENERAL,
            'label' => '网站Logo',
        ]);

        // hero_media 是多文件上传，保存为 JSON
        Setting::set('hero_media', $data['hero_media'] ?? [], [
            'type' => Setting::TYPE_JSON,
            'group' => Setting::GROUP_GENERAL,
            'label' => '首页展示媒体',
        ]);

        Setting::set('hero_video_url', $data['hero_video_url'] ?? '', [
            'type' => Setting::TYPE_STRING,
            'group' => Setting::GROUP_GENERAL,
            'label' => '首页视频链接',
        ]);

        // 保存 SEO 设置
        Setting::set('seo_keywords', $data['seo_keywords'] ?? '', [
            'type' => Setting::TYPE_STRING,
            'group' => Setting::GROUP_GENERAL,
            'label' => 'SEO 关键词',
        ]);

        Setting::set('seo_author', $data['seo_author'] ?? '', [
            'type' => Setting::TYPE_STRING,
            'group' => Setting::GROUP_GENERAL,
            'label' => 'SEO 作者',
        ]);

        Setting::set('og_image', $this->arrayToPath($data['og_image']), [
            'type' => Setting::TYPE_IMAGE,
            'group' => Setting::GROUP_GENERAL,
            'label' => '社交分享图片',
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
