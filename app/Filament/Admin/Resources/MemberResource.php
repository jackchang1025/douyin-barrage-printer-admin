<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MemberResource\Pages;
use App\Models\Member;
use App\Models\Plan;
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

    protected static ?string $navigationGroup = '订阅管理';

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
                            ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->minLength(6)
                            ->helperText('留空则不修改密码'),

                        Forms\Components\TextInput::make('avatar')
                            ->label('头像URL')
                            ->url()
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('订阅信息')
                    ->schema([
                        Forms\Components\Select::make('plan_id')
                            ->label('订阅计划')
                            ->relationship('planModel', 'name')
                            ->getOptionLabelFromRecordUsing(fn(Plan $record) => "{$record->name} ({$record->price_text}) - {$record->duration_text}")
                            ->preload()
                            ->searchable()
                            ->default(fn() => Plan::getDefault()?->id)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $plan = Plan::find($state);
                                    if ($plan) {
                                        $set('plan', $plan->code);
                                    }
                                }
                            }),

                        Forms\Components\Hidden::make('plan')
                            ->default('free'),

                        Forms\Components\Placeholder::make('subscription_expiry_display')
                            ->label('订阅过期时间')
                            ->content(fn(?Member $record): string => $record?->subscription_expiry?->format('Y-m-d H:i') ?? '永久有效')
                            ->visible(fn(string $operation): bool => $operation === 'edit')
                            ->helperText('订阅时间由订阅记录管理，请在"订阅记录"中修改'),
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
                    ->visible(fn(string $operation): bool => $operation === 'edit'),
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
                    ->formatStateUsing(fn(Member $record) => $record->country_code . ' ' . $record->phone)
                    ->searchable(['country_code', 'phone']),

                Tables\Columns\TextColumn::make('planModel.name')
                    ->label('订阅计划')
                    ->badge()
                    ->default(fn(Member $record): string => $record->plan_name)
                    ->color(fn(Member $record): string => $record->planModel?->color ?? 'gray'),

                Tables\Columns\TextColumn::make('latestSubscription.expires_at')
                    ->label('订阅到期')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->placeholder('永久'),

                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'active' => '正常',
                        'disabled' => '禁用',
                        'banned' => '封禁',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'disabled' => 'warning',
                        'banned' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('phone_verified_at')
                    ->label('手机验证')
                    ->boolean()
                    ->getStateUsing(fn(Member $record): bool => $record->phone_verified_at !== null),

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
                Tables\Filters\SelectFilter::make('plan_id')
                    ->label('订阅计划')
                    ->relationship('planModel', 'name')
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'active' => '正常',
                        'disabled' => '禁用',
                        'banned' => '封禁',
                    ]),

                Tables\Filters\Filter::make('subscription_expired')
                    ->label('订阅已过期')
                    ->query(fn($query) => $query->whereHas('latestSubscription', function ($q) {
                        $q->where('expires_at', '<', now());
                    })),

                Tables\Filters\Filter::make('phone_verified')
                    ->label('手机已验证')
                    ->query(fn($query) => $query->whereNotNull('phone_verified_at')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('extend_subscription')
                    ->label('延长订阅')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('plan_id')
                            ->label('订阅计划')
                            ->options(fn() => Plan::active()->where('price', '>', 0)->ordered()->pluck('name', 'id'))
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
                        $plan = Plan::find($data['plan_id']);
                        if (!$plan) {
                            return;
                        }

                        // 计算新的过期时间
                        $currentExpiry = $record->subscription_expiry ?? now();
                        if ($currentExpiry->isPast()) {
                            $currentExpiry = now();
                        }
                        $newExpiry = $currentExpiry->copy()->addDays($data['duration']);

                        // 更新会员的计划信息
                        $record->update([
                            'plan_id' => $plan->id,
                            'plan' => $plan->code,
                        ]);

                        // 更新或创建订阅记录（单一数据源）
                        $latestSubscription = $record->latestSubscription;

                        if ($latestSubscription) {
                            $latestSubscription->update([
                                'plan_id' => $plan->id,
                                'plan' => $plan->code,
                                'expires_at' => $newExpiry,
                                'status' => 'active',
                                'daily_print_limit' => $plan->daily_print_limit,
                                'filters_enabled' => $plan->filters_enabled,
                                'custom_template_enabled' => $plan->custom_template_enabled,
                                'api_access_enabled' => $plan->api_access_enabled,
                            ]);
                        } else {
                            // 如果没有订阅记录，创建一个新的
                            $record->subscriptions()->create([
                                'plan_id' => $plan->id,
                                'plan' => $plan->code,
                                'status' => 'active',
                                'starts_at' => now(),
                                'expires_at' => $newExpiry,
                                'daily_print_limit' => $plan->daily_print_limit,
                                'filters_enabled' => $plan->filters_enabled,
                                'custom_template_enabled' => $plan->custom_template_enabled,
                                'api_access_enabled' => $plan->api_access_enabled,
                            ]);
                        }
                    }),
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn(Member $record): string => $record->status === 'active' ? '禁用' : '启用')
                    ->icon(fn(Member $record): string => $record->status === 'active' ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                    ->color(fn(Member $record): string => $record->status === 'active' ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (Member $record) {
                        $record->update([
                            'status' => $record->status === 'active' ? 'disabled' : 'active',
                        ]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('删除')
                    ->modalHeading('删除会员')
                    ->modalDescription(fn(Member $record): string => "确定要删除会员「{$record->nickname}」吗？此操作将同时删除该会员的所有订阅记录和登录令牌，且无法恢复。")
                    ->modalSubmitActionLabel('确认删除')
                    ->successNotificationTitle('会员已删除')
                    ->before(function (Member $record) {
                        // 删除会员的所有订阅记录
                        $record->subscriptions()->delete();
                        // 删除会员的所有 API Token
                        $record->tokens()->delete();
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
