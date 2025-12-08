<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SubscriptionResource\Pages;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = '订阅记录';

    protected static ?string $modelLabel = '订阅';

    protected static ?string $pluralModelLabel = '订阅';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = '订阅管理';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('订阅信息')
                    ->schema([
                        Forms\Components\Select::make('member_id')
                            ->label('会员')
                            ->relationship('member', 'nickname')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->getOptionLabelFromRecordUsing(fn(Member $record) => "{$record->nickname} ({$record->country_code}{$record->phone})"),

                        Forms\Components\Select::make('plan_id')
                            ->label('订阅计划')
                            ->relationship('planModel', 'name')
                            ->getOptionLabelFromRecordUsing(fn(Plan $record) => "{$record->name} ({$record->price_text}) - {$record->duration_text}")
                            ->preload()
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state) {
                                    $plan = Plan::find($state);
                                    if ($plan) {
                                        $set('plan', $plan->code);
                                        $set('daily_print_limit', $plan->daily_print_limit);
                                        $set('filters_enabled', $plan->filters_enabled);
                                        $set('custom_template_enabled', $plan->custom_template_enabled);
                                        $set('api_access_enabled', $plan->api_access_enabled);

                                        // 自动计算过期时间（基于计划的默认时长）
                                        $startsAt = $get('starts_at') ?? now();
                                        if ($plan->duration_days > 0) {
                                            $set('expires_at', Carbon::parse($startsAt)->addDays($plan->duration_days));
                                        } elseif ($plan->duration_days === 0) {
                                            // 永久有效
                                            $set('expires_at', null);
                                        }
                                    }
                                }
                            }),

                        Forms\Components\Hidden::make('plan'),

                        Forms\Components\Select::make('status')
                            ->label('状态')
                            ->options([
                                'active' => '有效',
                                'cancelled' => '已取消',
                                'expired' => '已过期',
                            ])
                            ->default('active')
                            ->required(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('starts_at')
                                    ->label('开始时间')
                                    ->required()
                                    ->default(now())
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        // 开始时间变化时，重新计算过期时间
                                        $planId = $get('plan_id');
                                        if ($planId && $state) {
                                            $plan = Plan::find($planId);
                                            if ($plan && $plan->duration_days > 0) {
                                                $set('expires_at', Carbon::parse($state)->addDays($plan->duration_days));
                                            }
                                        }
                                    }),

                                Forms\Components\DateTimePicker::make('expires_at')
                                    ->label('过期时间')
                                    ->nullable()
                                    ->helperText('留空表示永久有效，选择计划后会自动计算'),
                            ]),
                    ]),

                Forms\Components\Section::make('功能配置')
                    ->schema([
                        Forms\Components\TextInput::make('daily_print_limit')
                            ->label('每日打印限制')
                            ->numeric()
                            ->default(10)
                            ->helperText('-1 表示无限制'),

                        Forms\Components\Toggle::make('filters_enabled')
                            ->label('启用过滤器')
                            ->default(false),

                        Forms\Components\Toggle::make('custom_template_enabled')
                            ->label('启用自定义模板')
                            ->default(false),

                        Forms\Components\Toggle::make('api_access_enabled')
                            ->label('启用 API 访问')
                            ->default(false),
                    ]),

                Forms\Components\Section::make('其他')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label('元数据')
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

                Tables\Columns\TextColumn::make('member.nickname')
                    ->label('会员')
                    ->searchable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('member.phone')
                    ->label('手机号')
                    ->formatStateUsing(fn(Subscription $record) => $record->member?->country_code . ' ' . $record->member?->phone),

                Tables\Columns\TextColumn::make('planModel.name')
                    ->label('计划')
                    ->badge()
                    ->default(fn(Subscription $record): string => $record->plan_name)
                    ->color(fn(Subscription $record): string => $record->planModel?->color ?? 'gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'active' => '有效',
                        'cancelled' => '已取消',
                        'expired' => '已过期',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'cancelled' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('daily_print_limit')
                    ->label('打印限制')
                    ->formatStateUsing(fn(int $state): string => $state === -1 ? '无限制' : (string) $state),

                Tables\Columns\IconColumn::make('filters_enabled')
                    ->label('过滤器')
                    ->boolean(),

                Tables\Columns\IconColumn::make('custom_template_enabled')
                    ->label('自定义模板')
                    ->boolean(),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('开始时间')
                    ->dateTime('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('过期时间')
                    ->dateTime('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('days_remaining')
                    ->label('剩余天数')
                    ->badge()
                    ->color(
                        fn(Subscription $record): string =>
                        $record->days_remaining > 30 ? 'success' : ($record->days_remaining > 7 ? 'warning' : 'danger')
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan_id')
                    ->label('订阅计划')
                    ->relationship('planModel', 'name')
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'active' => '有效',
                        'cancelled' => '已取消',
                        'expired' => '已过期',
                    ]),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('即将过期（7天内）')
                    ->query(fn($query) => $query->where('expires_at', '<=', now()->addDays(7))
                        ->where('expires_at', '>', now())
                        ->where('status', 'active')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('extend')
                    ->label('延长')
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
                    ->action(function (Subscription $record, array $data) {
                        $currentExpiry = $record->expires_at ?? now();
                        if ($currentExpiry->isPast()) {
                            $currentExpiry = now();
                        }
                        $newExpiry = $currentExpiry->copy()->addDays($data['duration']);

                        // 只需更新订阅记录（单一数据源）
                        $record->update([
                            'expires_at' => $newExpiry,
                            'status' => 'active',
                        ]);
                    }),
                Tables\Actions\Action::make('changePlan')
                    ->label('更换计划')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('plan_id')
                            ->label('新计划')
                            ->options(fn() => Plan::active()->ordered()->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->action(function (Subscription $record, array $data) {
                        $plan = Plan::find($data['plan_id']);
                        if (!$plan) {
                            return;
                        }

                        $record->update([
                            'plan_id' => $plan->id,
                            'plan' => $plan->code,
                            'daily_print_limit' => $plan->daily_print_limit,
                            'filters_enabled' => $plan->filters_enabled,
                            'custom_template_enabled' => $plan->custom_template_enabled,
                            'api_access_enabled' => $plan->api_access_enabled,
                        ]);

                        // 同步更新会员信息
                        $record->member?->update([
                            'plan_id' => $plan->id,
                            'plan' => $plan->code,
                        ]);
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('取消')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Subscription $record) {
                        $record->update(['status' => 'cancelled']);
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
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
