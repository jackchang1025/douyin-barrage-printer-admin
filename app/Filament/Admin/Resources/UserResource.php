<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = '用户管理';

    protected static ?string $modelLabel = '用户';

    protected static ?string $pluralModelLabel = '用户';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('用户名')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('邮箱')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('country_code')
                                    ->label('国家区号')
                                    ->placeholder('+86')
                                    ->maxLength(10),

                                Forms\Components\TextInput::make('phone')
                                    ->label('手机号码')
                                    ->tel()
                                    ->maxLength(20),
                            ]),

                        Forms\Components\TextInput::make('password')
                            ->label('密码')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(6)
                            ->helperText('留空则不修改密码'),
                    ]),

                Forms\Components\Section::make('订阅信息')
                    ->schema([
                        Forms\Components\Select::make('plan')
                            ->label('订阅计划')
                            ->options([
                                'free' => '免费版',
                                'pro' => '专业版',
                                'enterprise' => '企业版',
                            ])
                            ->default('free')
                            ->required(),

                        Forms\Components\DateTimePicker::make('subscription_expiry')
                            ->label('订阅过期时间')
                            ->nullable(),
                    ]),

                Forms\Components\Section::make('权限设置')
                    ->schema([
                        Forms\Components\Toggle::make('is_admin')
                            ->label('管理员权限')
                            ->helperText('开启后可访问管理后台')
                            ->default(false),
                    ]),

                Forms\Components\Section::make('验证状态')
                    ->schema([
                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('邮箱验证时间')
                            ->nullable(),

                        Forms\Components\DateTimePicker::make('phone_verified_at')
                            ->label('手机验证时间')
                            ->nullable(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('用户名')
                    ->searchable(),

                Tables\Columns\TextColumn::make('full_phone')
                    ->label('手机号码')
                    ->getStateUsing(fn (User $record) => $record->country_code . ' ' . $record->phone)
                    ->searchable(['country_code', 'phone']),

                Tables\Columns\TextColumn::make('email')
                    ->label('邮箱')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('plan')
                    ->label('订阅计划')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'free' => '免费版',
                        'pro' => '专业版',
                        'enterprise' => '企业版',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'free',
                        'success' => 'pro',
                        'warning' => 'enterprise',
                    ]),

                Tables\Columns\TextColumn::make('subscription_expiry')
                    ->label('订阅到期')
                    ->dateTime('Y-m-d')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_admin')
                    ->label('管理员')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('注册时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan')
                    ->label('订阅计划')
                    ->options([
                        'free' => '免费版',
                        'pro' => '专业版',
                        'enterprise' => '企业版',
                    ]),

                Tables\Filters\TernaryFilter::make('is_admin')
                    ->label('管理员'),

                Tables\Filters\Filter::make('subscription_expired')
                    ->label('订阅已过期')
                    ->query(fn ($query) => $query->where('subscription_expiry', '<', now())),

                Tables\Filters\Filter::make('phone_verified')
                    ->label('手机已验证')
                    ->query(fn ($query) => $query->whereNotNull('phone_verified_at')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('extend_subscription')
                    ->label('延长订阅')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('duration')
                            ->label('延长时间')
                            ->options([
                                7 => '7 天',
                                30 => '30 天',
                                90 => '90 天',
                                365 => '365 天',
                            ])
                            ->required(),
                    ])
                    ->action(function (User $record, array $data) {
                        $currentExpiry = $record->subscription_expiry ?? now();
                        if ($currentExpiry->isPast()) {
                            $currentExpiry = now();
                        }
                        $record->update([
                            'subscription_expiry' => $currentExpiry->addDays($data['duration']),
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

