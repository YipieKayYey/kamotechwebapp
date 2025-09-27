<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromotionResource\Pages;
use App\Models\AirconType;
use App\Models\Promotion;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PromotionResource extends Resource
{
    protected static ?string $model = Promotion::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Website Management';

    protected static ?string $navigationLabel = 'Promotions';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Slider Content')
                    ->description('Configure how this promotion appears in the hero slider')
                    ->schema([
                        Forms\Components\TextInput::make('welcome_text')
                            ->label('Welcome Text')
                            ->placeholder('e.g., Kamotech Aircon Services')
                            ->helperText('Small text above the main title'),

                        Forms\Components\TextInput::make('title')
                            ->label('Main Title')
                            ->required()
                            ->placeholder('e.g., PRICE STARTS AT 450 PESOS!')
                            ->helperText('The big, attention-grabbing headline'),

                        Forms\Components\Textarea::make('subtitle')
                            ->label('Subtitle/Description')
                            ->rows(2)
                            ->placeholder('e.g., Find the affordable, Find your satisfaction!')
                            ->helperText('Supporting text below the title'),

                        Forms\Components\FileUpload::make('background_image')
                            ->label('Background Image')
                            ->image()
                            ->directory('promotions')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('16:9')
                            ->imageResizeTargetWidth('1920')
                            ->imageResizeTargetHeight('1080')
                            ->helperText('Recommended size: 1920x1080px (16:9 ratio)'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Call-to-Action Buttons')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('primary_button_text')
                                ->label('Primary Button Text')
                                ->default('BOOK NOW')
                                ->required(),

                            Forms\Components\TextInput::make('primary_button_link')
                                ->label('Primary Button Link')
                                ->default('/booking')
                                ->required()
                                ->helperText('Use /booking for booking page, /register for signup, etc.'),
                        ])->columns(2),

                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('secondary_button_text')
                                ->label('Secondary Button Text')
                                ->placeholder('e.g., LEARN MORE'),

                            Forms\Components\TextInput::make('secondary_button_link')
                                ->label('Secondary Button Link')
                                ->placeholder('e.g., #services or /contact')
                                ->helperText('Leave empty to hide secondary button'),
                        ])->columns(2),
                    ]),

                Forms\Components\Section::make('Promotion Details')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\Select::make('discount_type')
                                ->label('Discount Type')
                                ->options([
                                    'percentage' => 'Percentage Off',
                                    'fixed' => 'Fixed Amount Off',
                                    'free_service' => 'Free Service',
                                ])
                                ->reactive(),

                            Forms\Components\TextInput::make('discount_value')
                                ->label('Discount Value')
                                ->numeric()
                                ->prefix(fn (Forms\Get $get) => $get('discount_type') === 'fixed' ? '₱' : null)
                                ->suffix(fn (Forms\Get $get) => $get('discount_type') === 'percentage' ? '%' : null)
                                ->visible(fn (Forms\Get $get) => in_array($get('discount_type'), ['percentage', 'fixed'])),

                            Forms\Components\TextInput::make('promo_code')
                                ->label('Promo Code')
                                ->placeholder('e.g., SEPT2025')
                                ->helperText('Optional: For tracking or future implementation'),
                        ])->columns(3),

                        Forms\Components\Select::make('applicable_services')
                            ->label('Applicable Services')
                            ->multiple()
                            ->options(Service::pluck('name', 'id'))
                            ->helperText('Leave empty to apply to all services'),

                        Forms\Components\Select::make('applicable_aircon_types')
                            ->label('Applicable AC Types')
                            ->multiple()
                            ->options(AirconType::pluck('name', 'id'))
                            ->helperText('Leave empty to apply to all AC types'),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Display Settings')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\DatePicker::make('start_date')
                                ->label('Start Date')
                                ->required()
                                ->default(now()),

                            Forms\Components\DatePicker::make('end_date')
                                ->label('End Date')
                                ->required()
                                ->default(now()->addMonth())
                                ->after('start_date'),
                        ])->columns(2),

                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('display_order')
                                ->label('Display Order')
                                ->numeric()
                                ->default(0)
                                ->helperText('Lower numbers appear first in the slider'),

                            Forms\Components\Toggle::make('is_active')
                                ->label('Is Active')
                                ->default(true)
                                ->helperText('Only active promotions within date range will show'),

                            Forms\Components\Toggle::make('show_on_slider')
                                ->label('Show on Homepage Slider')
                                ->default(true)
                                ->helperText('Turn off to use promotion for other purposes'),
                        ])->columns(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('background_image')
                    ->label('Image')
                    ->square()
                    ->size(60),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 30 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('subtitle')
                    ->label('Subtitle')
                    ->limit(40)
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('formatted_discount')
                    ->label('Discount')
                    ->colors([
                        'success' => fn ($state) => str_contains($state ?? '', 'FREE'),
                        'warning' => fn ($state) => str_contains($state ?? '', '%'),
                        'primary' => fn ($state) => str_contains($state ?? '', '₱'),
                    ]),

                Tables\Columns\TextColumn::make('display_order')
                    ->label('Order')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('show_on_slider')
                    ->label('On Slider')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('End')
                    ->date('M j, Y')
                    ->sortable()
                    ->color(fn (Promotion $record) => $record->end_date < now() ? 'danger' : null),
            ])
            ->defaultSort('display_order', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),

                Tables\Filters\TernaryFilter::make('show_on_slider')
                    ->label('Show on Slider'),

                Tables\Filters\Filter::make('valid')
                    ->label('Currently Valid')
                    ->query(fn (Builder $query) => $query->active()),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->modalHeading('Promotion Preview')
                    ->modalContent(fn (Promotion $record) => view('filament.promotions.preview', ['promotion' => $record]))
                    ->modalSubmitAction(false),

                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-m-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function (Promotion $record) {
                        $new = $record->replicate();
                        $new->title = $record->title.' (Copy)';
                        $new->is_active = false;
                        $new->display_order = Promotion::max('display_order') + 1;
                        $new->save();

                        \Filament\Notifications\Notification::make()
                            ->title('Promotion duplicated')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->update(['is_active' => true])),

                Tables\Actions\BulkAction::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->update(['is_active' => false])),

                // Delete actions disabled as per panelist requirement
            ])
            ->reorderable('display_order');
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
            'index' => Pages\ListPromotions::route('/'),
            'create' => Pages\CreatePromotion::route('/create'),
            'edit' => Pages\EditPromotion::route('/{record}/edit'),
        ];
    }
}
