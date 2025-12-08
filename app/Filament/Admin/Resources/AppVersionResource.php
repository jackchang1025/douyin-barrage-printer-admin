<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AppVersionResource\Pages;
use App\Models\AppVersion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class AppVersionResource extends Resource
{
    protected static ?string $model = AppVersion::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-circle';

    protected static ?string $navigationLabel = '版本管理';

    protected static ?string $modelLabel = '客户端版本';

    protected static ?string $pluralModelLabel = '客户端版本';

    protected static ?string $navigationGroup = '系统管理';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('版本信息')
                    ->schema([
                        Forms\Components\TextInput::make('version')
                            ->label('版本号')
                            ->required()
                            ->maxLength(20)
                            ->placeholder('如：1.1.0')
                            ->helperText('格式：主版本.次版本.修订版本'),

                        Forms\Components\Select::make('platform')
                            ->label('平台')
                            ->options([
                                'win' => 'Windows',
                                'mac' => 'macOS',
                                'linux' => 'Linux',
                            ])
                            ->required()
                            ->default('win'),

                        Forms\Components\FileUpload::make('file_path')
                            ->label('安装包文件')
                            ->disk('public')
                            ->directory('app-releases')
                            ->preserveFilenames()
                            ->maxSize(512000) // 500MB
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('file_name')
                            ->label('文件名')
                            ->readOnly()
                            ->helperText('保存后自动填充'),

                        Forms\Components\TextInput::make('file_size')
                            ->label('文件大小(字节)')
                            ->readOnly()
                            ->numeric()
                            ->helperText('保存后自动填充'),

                        Forms\Components\Textarea::make('sha512')
                            ->label('SHA512')
                            ->readOnly()
                            ->rows(2)
                            ->helperText('保存后自动填充')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('发布设置')
                    ->schema([
                        Forms\Components\RichEditor::make('release_notes')
                            ->label('更新日志')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_mandatory')
                            ->label('强制更新')
                            ->helperText('开启后用户必须更新才能继续使用'),

                        Forms\Components\Toggle::make('is_published')
                            ->label('发布')
                            ->helperText('发布后用户可以下载和更新')
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $set('published_at', now());
                                }
                            }),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('发布时间'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('version')
                    ->label('版本号')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('platform')
                    ->label('平台')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'win' => 'Windows',
                        'mac' => 'macOS',
                        'linux' => 'Linux',
                        default => $state,
                    })
                    ->color(fn($state) => match ($state) {
                        'win' => 'info',
                        'mac' => 'warning',
                        'linux' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('file_size')
                    ->label('大小')
                    ->formatStateUsing(fn($state) => $state ? number_format($state / 1048576, 1) . ' MB' : '-'),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('已发布')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_mandatory')
                    ->label('强制更新')
                    ->boolean(),

                Tables\Columns\TextColumn::make('download_count')
                    ->label('下载次数')
                    ->sortable(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('发布时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('platform')
                    ->label('平台')
                    ->options([
                        'win' => 'Windows',
                        'mac' => 'macOS',
                        'linux' => 'Linux',
                    ]),
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('发布状态'),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('下载')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn(AppVersion $record) => $record->download_url)
                    ->openUrlInNewTab()
                    ->visible(fn(AppVersion $record) => $record->file_path),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppVersions::route('/'),
            'create' => Pages\CreateAppVersion::route('/create'),
            'edit' => Pages\EditAppVersion::route('/{record}/edit'),
        ];
    }
}
