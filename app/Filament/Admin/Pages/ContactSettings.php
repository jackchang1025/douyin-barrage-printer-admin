<?php

namespace App\Filament\Admin\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class ContactSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-phone';

    protected static string $view = 'filament.admin.pages.contact-settings';

    protected static ?string $navigationLabel = '联系方式设置';

    protected static ?string $title = '联系方式设置';

    protected static ?string $navigationGroup = '系统设置';

    protected static ?int $navigationSort = 100;

    public ?array $data = [];

    public function mount(): void
    {
        // FileUpload 组件期望数组格式
        $wechatQrcode = Setting::get('contact_wechat_qrcode', '');
        $qqQrcode = Setting::get('contact_qq_qrcode', '');

        $this->form->fill([
            'contact_phone' => Setting::get('contact_phone', ''),
            'contact_wechat_qrcode' => $wechatQrcode ? [$wechatQrcode] : [],
            'contact_qq_qrcode' => $qqQrcode ? [$qqQrcode] : [],
            'contact_description' => Setting::get('contact_description', '如需续费，请联系客服'),
            'renewal_guide' => Setting::get('renewal_guide', '扫描下方二维码或拨打客服电话，提供您的账号信息即可完成续费'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('联系方式')
                    ->description('设置客服联系方式，用于订阅过期用户续费咨询')
                    ->schema([
                        Forms\Components\TextInput::make('contact_phone')
                            ->label('联系电话')
                            ->placeholder('请输入客服电话')
                            ->tel()
                            ->maxLength(20)
                            ->helperText('客服联系电话，例如：400-123-4567'),

                        Forms\Components\FileUpload::make('contact_wechat_qrcode')
                            ->label('微信二维码')
                            ->image()
                            ->disk('public')
                            ->directory('qrcodes')
                            ->imageResizeMode('contain')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('300')
                            ->imageResizeTargetHeight('300')
                            ->helperText('上传微信客服二维码图片（建议尺寸 300x300）'),

                        Forms\Components\FileUpload::make('contact_qq_qrcode')
                            ->label('QQ二维码')
                            ->image()
                            ->disk('public')
                            ->directory('qrcodes')
                            ->imageResizeMode('contain')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('300')
                            ->imageResizeTargetHeight('300')
                            ->helperText('上传 QQ 客服二维码图片（建议尺寸 300x300）'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('显示文案')
                    ->description('订阅过期页面显示的文字内容')
                    ->schema([
                        Forms\Components\Textarea::make('contact_description')
                            ->label('联系说明')
                            ->placeholder('如需续费，请联系客服')
                            ->rows(2)
                            ->maxLength(200)
                            ->helperText('显示在订阅过期页面的主要说明文字'),

                        Forms\Components\Textarea::make('renewal_guide')
                            ->label('续费指引')
                            ->placeholder('扫描下方二维码或拨打客服电话...')
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('详细的续费操作指引'),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // 保存每个设置项
        Setting::set('contact_phone', $data['contact_phone'] ?? '', [
            'type' => Setting::TYPE_STRING,
            'group' => Setting::GROUP_CONTACT,
            'label' => '联系电话',
        ]);

        // FileUpload 返回数组，需要提取第一个值
        $wechatQrcode = $data['contact_wechat_qrcode'] ?? '';
        if (is_array($wechatQrcode)) {
            $wechatQrcode = $wechatQrcode[0] ?? '';
        }
        Setting::set('contact_wechat_qrcode', $wechatQrcode, [
            'type' => Setting::TYPE_IMAGE,
            'group' => Setting::GROUP_CONTACT,
            'label' => '微信二维码',
        ]);

        $qqQrcode = $data['contact_qq_qrcode'] ?? '';
        if (is_array($qqQrcode)) {
            $qqQrcode = $qqQrcode[0] ?? '';
        }
        Setting::set('contact_qq_qrcode', $qqQrcode, [
            'type' => Setting::TYPE_IMAGE,
            'group' => Setting::GROUP_CONTACT,
            'label' => 'QQ二维码',
        ]);

        Setting::set('contact_description', $data['contact_description'] ?? '', [
            'type' => Setting::TYPE_TEXT,
            'group' => Setting::GROUP_CONTACT,
            'label' => '联系说明',
        ]);

        Setting::set('renewal_guide', $data['renewal_guide'] ?? '', [
            'type' => Setting::TYPE_TEXT,
            'group' => Setting::GROUP_CONTACT,
            'label' => '续费指引',
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

