<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MemberResource\Pages;
use App\Models\Member;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = '会员管理';

    protected static ?string $modelLabel = '会员';

    protected static ?string $pluralModelLabel = '会员';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\TextInput::make('nickname')
                            ->label('昵称')
                            ->maxLength(255),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('country_code')
                                    ->label('国家区号')
                                    ->placeholder('+86')
                                    ->required()
                                    ->maxLength(10),

                                Forms\Components\TextInput::make('phone')
                                    ->label('手机号码')
                                    ->tel()
                                    ->required()
                                    ->maxLength(20),
                            ]),

                        Forms\Components\TextInput::make('password')
                            ->label('密码')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->minLength(6)
                            ->helperText('留空则不修改密码'),

                        Forms\Components\TextInput::make('avatar')
                            ->label('头像URL')
                            ->url()
                            ->maxLength(255),
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

                Forms\Components\Section::make('账号状态')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('状态')
                            ->options([
                                'active' => '正常',
                                'disabled' => '禁用',
                                'banned' => '封禁',
                            ])
                            ->default('active')
                            ->required(),

                        Forms\Components\DateTimePicker::make('phone_verified_at')
                            ->label('手机验证时间')
                            ->nullable(),
                    ]),

                Forms\Components\Section::make('登录信息')
                    ->schema([
                        Forms\Components\DateTimePicker::make('last_login_at')
                            ->label('最后登录时间')
                            ->disabled(),

                        Forms\Components\TextInput::make('last_login_ip')
                            ->label('最后登录IP')
                            ->disabled(),
                    ])
                    ->collapsed()
                    ->visible(fn (string $operation): bool => $operation === 'edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('nickname')
                    ->label('昵称')
                    ->searchable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('full_phone')
                    ->label('手机号码')
                    ->formatStateUsing(fn (Member $record) => $record->country_code . ' ' . $record->phone)
                    ->searchable(['country_code', 'phone']),

                Tables\Columns\TextColumn::make('plan')
                    ->label('订阅计划')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'free' => '免费版',
                        'pro' => '专业版',
                        'enterprise' => '企业版',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'free' => 'gray',
                        'pro' => 'success',
                        'enterprise' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('subscription_expiry')
                    ->label('订阅到期')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => '正常',
                        'disabled' => '禁用',
                        'banned' => '封禁',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'disabled' => 'warning',
                        'banned' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('phone_verified_at')
                    ->label('手机验证')
                    ->boolean()
                    ->getStateUsing(fn (Member $record): bool => $record->phone_verified_at !== null),

                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('最后登录')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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

                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'active' => '正常',
                        'disabled' => '禁用',
                        'banned' => '封禁',
                    ]),

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
                        Forms\Components\Select::make('plan')
                            ->label('订阅计划')
                            ->options([
                                'pro' => '专业版',
                                'enterprise' => '企业版',
                            ])
                            ->required(),
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
                    ->action(function (Member $record, array $data) {
                        $currentExpiry = $record->subscription_expiry ?? now();
                        if ($currentExpiry->isPast()) {
                            $currentExpiry = now();
                        }
                        $record->update([
                            'plan' => $data['plan'],
                            'subscription_expiry' => $currentExpiry->addDays($data['duration']),
                        ]);
                    }),
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (Member $record): string => $record->status === 'active' ? '禁用' : '启用')
                    ->icon(fn (Member $record): string => $record->status === 'active' ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                    ->color(fn (Member $record): string => $record->status === 'active' ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (Member $record) {
                        $record->update([
                            'status' => $record->status === 'active' ? 'disabled' : 'active',
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
            'index' => Pages\ListMembers::route('/'),
            'create' => Pages\CreateMember::route('/create'),
            'edit' => Pages\EditMember::route('/{record}/edit'),
        ];
    }
}

