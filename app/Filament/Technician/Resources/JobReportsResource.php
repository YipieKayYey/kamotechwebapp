<?php

namespace App\Filament\Technician\Resources;

use App\Models\RatingReview;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class JobReportsResource extends Resource
{
    protected static ?string $model = RatingReview::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';
    
    protected static ?string $navigationLabel = 'Customer Reviews';
    
    protected static ?string $title = 'Customer Reviews';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $technician = $user->technician;

        if (!$technician) {
            return parent::getEloquentQuery()->whereRaw('1 = 0'); // Return empty query
        }

        return parent::getEloquentQuery()
            ->where('technician_id', $technician->id)
            ->where('is_approved', true)
            ->with(['customer', 'booking.service']);
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();
        $technician = $user->technician;

        if (!$technician) {
            return null;
        }

        $newReviews = RatingReview::where('technician_id', $technician->id)
            ->where('is_approved', true)
            ->whereDate('created_at', '>', now()->subDays(7))
            ->count();

        return $newReviews > 0 ? (string) $newReviews : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Job Information')
                    ->schema([
                        Forms\Components\TextInput::make('job_number')
                            ->label('Job #')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->booking?->booking_number),

                        Forms\Components\TextInput::make('service_name')
                            ->label('Service')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->booking?->service?->name),

                        Forms\Components\TextInput::make('job_date')
                            ->label('Job Date')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->booking?->scheduled_date?->format('M j, Y')),

                        Forms\Components\TextInput::make('customer_name')
                            ->label('Customer')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->customer?->name),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Rating & Review')
                    ->schema([
                        Forms\Components\TextInput::make('overall_rating')
                            ->label('Overall Rating')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->overall_rating . '/5 ⭐'),

                        Forms\Components\TextInput::make('review_date')
                            ->label('Review Date')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->created_at?->format('M j, Y g:i A')),

                        Forms\Components\Textarea::make('review')
                            ->label('Customer Comments')
                            ->disabled()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Review Status')
                    ->schema([
                        Forms\Components\TextInput::make('approval_status')
                            ->label('Approval Status')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->is_approved ? 'Approved' : 'Pending Approval'),

                        Forms\Components\TextInput::make('service_type')
                            ->label('Service Type')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->service?->name),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('booking.booking_number')
                    ->label('Job #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('booking.service.name')
                    ->label('Service')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('overall_rating')
                    ->label('Rating')
                    ->formatStateUsing(fn ($state) => $state . '/5')
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state >= 4.5 => 'success',
                        $state >= 4.0 => 'warning', 
                        $state >= 3.0 => 'danger',
                        default => 'gray'
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('review')
                    ->label('Comment')
                    ->limit(50)
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('overall_rating')
                    ->label('Rating')
                    ->options([
                        '5' => '5 Stars ⭐⭐⭐⭐⭐',
                        '4' => '4+ Stars ⭐⭐⭐⭐',
                        '3' => '3+ Stars ⭐⭐⭐',
                        '2' => '2+ Stars ⭐⭐',
                        '1' => '1+ Stars ⭐',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => 
                        $query->when($data['value'], fn ($query, $rating) => 
                            $query->where('overall_rating', '>=', $rating)
                        )
                    ),

                Tables\Filters\SelectFilter::make('service')
                    ->label('Service Type')
                    ->relationship('booking.service', 'name'),

                Tables\Filters\Filter::make('this_month')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('created_at', now()->month))
                    ->label('This Month'),

                Tables\Filters\Filter::make('last_30_days')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(30)))
                    ->label('Last 30 Days'),

                Tables\Filters\Filter::make('high_rating')
                    ->query(fn (Builder $query): Builder => $query->where('overall_rating', '>=', 4))
                    ->label('High Rating (4+ stars)'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View Review'),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Technician\Resources\JobReportsResource\Pages\ListJobReports::route('/'),
            'view' => \App\Filament\Technician\Resources\JobReportsResource\Pages\ViewJobReport::route('/{record}'),
        ];
    }
}
