<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RatingReviewResource\Pages;
use App\Filament\Resources\RatingReviewResource\RelationManagers;
use App\Models\RatingReview;
use App\Models\ReviewCategory;
use App\Models\CategoryScore;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RatingReviewResource extends Resource
{
    protected static ?string $model = RatingReview::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'Review Management';
    protected static ?string $navigationLabel = 'Customer Reviews';
    protected static ?string $modelLabel = 'Customer Review';
    protected static ?string $pluralModelLabel = 'Customer Reviews';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Select Booking')
                    ->description('Choose a completed booking to review')
                    ->schema([
                        Forms\Components\Select::make('booking_id')
                            ->label('Booking')
                            ->options(function () {
                                return \App\Models\Booking::with(['customer', 'technician.user', 'service'])
                                    ->where('status', 'completed')
                                    ->get()
                                    ->mapWithKeys(function ($booking) {
                                        $customerName = $booking->customer ? $booking->customer->name : ($booking->customer_name ?? 'Unknown');
                                        $technicianName = $booking->technician ? $booking->technician->user->name : 'No Technician';
                                        $serviceName = $booking->service ? $booking->service->name : 'Unknown Service';
                                        
                                        return [
                                            $booking->id => "{$booking->booking_number} - {$customerName} | {$technicianName} | {$serviceName}"
                                        ];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    // Get the booking with its relationships
                                    $booking = \App\Models\Booking::with(['customer', 'technician', 'service'])->find($state);
                                    
                                    if ($booking) {
                                        // Auto-populate related fields from booking
                                        $set('customer_id', $booking->customer_id);
                                        $set('technician_id', $booking->technician_id);
                                        $set('service_id', $booking->service_id);
                                    }
                                } else {
                                    // Clear related fields if booking is cleared
                                    $set('customer_id', null);
                                    $set('technician_id', null);
                                    $set('service_id', null);
                                }
                            })
                            ->helperText('Only completed bookings are available for review')
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('Booking Details (Auto-populated)')
                    ->description('These fields are automatically filled from the selected booking')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name')
                            ->disabled()
                            ->dehydrated()
                            ->helperText('✅ Auto-filled from selected booking'),
                            
                        Forms\Components\Select::make('technician_id')
                            ->label('Technician')
                            ->relationship('technician.user', 'name')
                            ->disabled()
                            ->dehydrated()
                            ->helperText('✅ Auto-filled from selected booking'),
                            
                        Forms\Components\Select::make('service_id')
                            ->label('Service')
                            ->relationship('service', 'name')
                            ->disabled()
                            ->dehydrated()
                            ->helperText('✅ Auto-filled from selected booking'),
                    ])
                    ->columns(3),
                    
                Forms\Components\Section::make('Category Ratings')
                    ->description('Rate each category from 1-5 stars')
                    ->schema(function (?RatingReview $record) {
                        $categories = ReviewCategory::getActiveOrdered();
                        $fields = [];
                        
                        foreach ($categories as $category) {
                            $fields[] = Forms\Components\Select::make("category_score_{$category->id}")
                                ->label("⭐ {$category->name}")
                                ->options([
                                    1 => '⭐ 1 Star',
                                    2 => '⭐⭐ 2 Stars',
                                    3 => '⭐⭐⭐ 3 Stars',
                                    4 => '⭐⭐⭐⭐ 4 Stars', 
                                    5 => '⭐⭐⭐⭐⭐ 5 Stars',
                                ])
                                ->required()
                                ->placeholder('Select rating')
                                ->helperText($category->description ?: '')
                                ->default(function () use ($record, $category) {
                                    if ($record) {
                                        $score = $record->categoryScores()->where('category_id', $category->id)->first();
                                        return $score?->score;
                                    }
                                    return null;
                                });
                        }
                        
                        return $fields;
                    })
                    ->columns(2),
                    
                Forms\Components\Section::make('Review Details')
                    ->description('Customer feedback and approval')
                    ->schema([
                        Forms\Components\Textarea::make('review')
                            ->label('📝 Review Text')
                            ->rows(4)
                            ->maxLength(1000)
                            ->placeholder('Enter customer feedback and comments about the service...')
                            ->columnSpanFull(),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('overall_rating')
                                    ->label('Overall Rating (Auto-calculated)')
                                    ->disabled()
                                    ->helperText('Automatically calculated from category scores')
                                    ->dehydrated(false)
                                    ->default(function (?RatingReview $record) {
                                        if ($record && $record->overall_rating !== null) {
                                            return round((float) $record->overall_rating, 2) . ' ⭐';
                                        }
                                        return 'Will calculate after saving...';
                                    }),
                                    
                                Forms\Components\Toggle::make('is_approved')
                                    ->label('✅ Approved for Display')
                                    ->helperText('Whether this review should be visible to customers')
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking.booking_number')
                    ->label('Booking #')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('technician.user.name')
                    ->label('Technician')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('overall_rating')
                    ->label('Rating')
                    ->colors([
                        'danger' => fn ($state) => $state < 2,
                        'warning' => fn ($state) => $state >= 2 && $state < 3,
                        'primary' => fn ($state) => $state >= 3 && $state < 4,
                        'success' => fn ($state) => $state >= 4,
                    ])
                    ->formatStateUsing(fn ($state) => $state ? round($state, 1) . ' ★' : 'N/A')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('review')
                    ->label('Review')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                    
                Tables\Columns\IconColumn::make('is_approved')
                    ->label('Approved')
                    ->boolean()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('overall_rating')
                    ->label('Rating Range')
                    ->options([
                        '1-2' => '⭐ 1-2 Stars (Poor)',
                        '2-3' => '⭐⭐ 2-3 Stars (Fair)',
                        '3-4' => '⭐⭐⭐ 3-4 Stars (Good)',
                        '4-5' => '⭐⭐⭐⭐ 4-5 Stars (Excellent)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            function (Builder $query, $range): Builder {
                                [$min, $max] = explode('-', $range);
                                return $query->whereBetween('overall_rating', [(float) $min, (float) $max]);
                            }
                        );
                    }),
                    
                Tables\Filters\SelectFilter::make('is_approved')
                    ->label('Approval Status')
                    ->options([
                        1 => 'Approved',
                        0 => 'Pending',
                    ]),
                    
                Tables\Filters\SelectFilter::make('technician_id')
                    ->label('Technician')
                    ->options(function () {
                        return \App\Models\Technician::with('user')
                            ->get()
                            ->pluck('user.name', 'id');
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Delete actions disabled as per panelist requirement
                ]),
            ]);
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
            'index' => Pages\ListRatingReviews::route('/'),
            'create' => Pages\CreateRatingReview::route('/create'),
            'view' => Pages\ViewRatingReview::route('/{record}'),
            'edit' => Pages\EditRatingReview::route('/{record}/edit'),
        ];
    }
}
