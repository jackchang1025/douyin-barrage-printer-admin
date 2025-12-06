<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\VerificationCodeResource\Pages;
use App\Models\VerificationCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VerificationCodeResource extends Resource
{
    protected static ?string $model = VerificationCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = '验证码记录';

    protected static ?string $modelLabel = '验证码';

    protected static ?string $pluralModelLabel = '验证码';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('country_code')
                    ->label('国家区号')
                    ->required(),

                Forms\Components\TextInput::make('phone')
                    ->label('手机号码')
                    ->required(),

                Forms\Components\TextInput::make('code')
                    ->label('验证码')
                    ->required(),

                Forms\Components\Select::make('type')
                    ->label('类型')
                    ->options([
                        'login' => '登录',
                        'register' => '注册',
                        'reset' => '重置密码',
                    ])
                    ->required(),

                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('过期时间')
                    ->required(),

                Forms\Components\Toggle::make('is_used')
                    ->label('已使用'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('country_code')
                    ->label('区号'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('手机号')
                    ->searchable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('验证码')
                    ->copyable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('类型')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'login' => '登录',
                        'register' => '注册',
                        'reset' => '重置',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'login',
                        'success' => 'register',
                        'warning' => 'reset',
                    ]),

                Tables\Columns\IconColumn::make('is_used')
                    ->label('已使用')
                    ->boolean(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('过期时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('类型')
                    ->options([
                        'login' => '登录',
                        'register' => '注册',
                        'reset' => '重置密码',
                    ]),

                Tables\Filters\TernaryFilter::make('is_used')
                    ->label('已使用'),

                Tables\Filters\Filter::make('expired')
                    ->label('已过期')
                    ->query(fn ($query) => $query->where('expires_at', '<', now())),
            ])
            ->actions([
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
            'index' => Pages\ListVerificationCodes::route('/'),
        ];
    }

    // 禁止创建和编辑
    public static function canCreate(): bool
    {
        return false;
    }
}

