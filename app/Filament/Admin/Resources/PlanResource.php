<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = '计划管理';

    protected static ?string $modelLabel = '计划';

    protected static ?string $pluralModelLabel = '计划';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationGroup = '订阅管理';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('计划标识')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->regex('/^[a-z0-9_]+$/')
                            ->helperText('只能使用小写字母、数字和下划线，如：free, pro, vip_monthly')
                            ->disabled(fn(?Plan $record) => $record !== null && in_array($record->code, [Plan::CODE_FREE, Plan::CODE_PRO, Plan::CODE_ENTERPRISE])),

                        Forms\Components\TextInput::make('name')
                            ->label('计划名称')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\Textarea::make('description')
                            ->label('计划描述')
                            ->maxLength(500)
                            ->rows(3),
                    ]),

                Forms\Components\Section::make('价格与时长')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->label('价格（元）')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->prefix('¥'),

                                Forms\Components\TextInput::make('duration_days')
                                    ->label('默认时长（天）')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(30)
                                    ->helperText('0 表示永久有效'),
                            ]),
                    ]),

                Forms\Components\Section::make('功能配置')
                    ->schema([
                        Forms\Components\TextInput::make('daily_print_limit')
                            ->label('每日打印限制')
                            ->required()
                            ->numeric()
                            ->default(10)
                            ->helperText('-1 表示无限制'),

                        Forms\Components\Grid::make(3)
                            ->schema([
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
                    ]),

                Forms\Components\Section::make('显示设置')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('color')
                                    ->label('显示颜色')
                                    ->options([
                                        'gray' => '灰色',
                                        'success' => '绿色',
                                        'warning' => '黄色',
                                        'danger' => '红色',
                                        'info' => '蓝色',
                                        'primary' => '主色',
                                    ])
                                    ->default('gray')
                                    ->required(),

                                Forms\Components\TextInput::make('sort_order')
                                    ->label('排序顺序')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('数字越小越靠前'),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('启用')
                                    ->default(true),
                            ]),

                        Forms\Components\Toggle::make('is_default')
                            ->label('设为默认计划')
                            ->helperText('新用户注册时自动获得的计划')
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, ?Plan $record) {
                                // 如果设置为默认，自动设置价格为0
                                if ($state) {
                                    $set('price', 0);
                                }
                            }),
                    ]),

                Forms\Components\Section::make('功能特性列表')
                    ->schema([
                        Forms\Components\Repeater::make('features')
                            ->label('功能特性')
                            ->simple(
                                Forms\Components\TextInput::make('feature')
                                    ->required()
                                    ->placeholder('输入功能描述')
                            )
                            ->defaultItems(0)
                            ->addActionLabel('添加功能特性')
                            ->helperText('用于在前端展示计划的功能列表'),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('排序')
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('标识')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('名称')
                    ->searchable()
                    ->badge()
                    ->color(fn(Plan $record): string => $record->color),

                Tables\Columns\TextColumn::make('price')
                    ->label('价格')
                    ->formatStateUsing(fn(Plan $record): string => $record->price_text),

                Tables\Columns\TextColumn::make('duration_days')
                    ->label('时长')
                    ->formatStateUsing(fn(Plan $record): string => $record->duration_text),

                Tables\Columns\TextColumn::make('daily_print_limit')
                    ->label('打印限制')
                    ->formatStateUsing(fn(Plan $record): string => $record->print_limit_text),

                Tables\Columns\IconColumn::make('filters_enabled')
                    ->label('过滤器')
                    ->boolean(),

                Tables\Columns\IconColumn::make('custom_template_enabled')
                    ->label('自定义模板')
                    ->boolean(),

                Tables\Columns\IconColumn::make('api_access_enabled')
                    ->label('API')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('启用')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('默认')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->trueColor('warning'),

                Tables\Columns\TextColumn::make('subscriptions_count')
                    ->label('订阅数')
                    ->counts('subscriptions')
                    ->sortable(),

                Tables\Columns\TextColumn::make('members_count')
                    ->label('会员数')
                    ->counts('members')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('启用状态'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('setDefault')
                    ->label('设为默认')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->hidden(fn(Plan $record): bool => $record->is_default)
                    ->action(function (Plan $record) {
                        // 取消其他默认计划
                        Plan::where('is_default', true)->update(['is_default' => false]);
                        // 设置当前计划为默认
                        $record->update(['is_default' => true]);
                    }),
                Tables\Actions\Action::make('duplicate')
                    ->label('复制')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (Plan $record) {
                        $newPlan = $record->replicate();
                        $newPlan->code = $record->code . '_copy_' . time();
                        $newPlan->name = $record->name . ' (副本)';
                        $newPlan->is_default = false;
                        $newPlan->save();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            // 检查是否有正在使用的计划
                            foreach ($records as $record) {
                                if ($record->subscriptions()->exists() || $record->members()->exists()) {
                                    throw new \Exception("计划 \"{$record->name}\" 正在被使用，无法删除");
                                }
                                if ($record->is_default) {
                                    throw new \Exception("无法删除默认计划 \"{$record->name}\"");
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
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
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
