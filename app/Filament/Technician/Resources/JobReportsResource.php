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

        if (! $technician) {
            return parent::getEloquentQuery()->whereRaw('1 = 0'); // Return empty query
        }

        return parent::getEloquentQuery()
            ->where('technician_id', $technician->id)
            ->where('is_approved', true)
            ->with(['customer', 'booking.service'])
            ->inRandomOrder(); // Randomize the order of reviews
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();
        $technician = $user->technician;

        if (! $technician) {
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
                Forms\Components\Section::make('Service Information')
                    ->schema([
                        Forms\Components\TextInput::make('service_name')
                            ->label('Service Type')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->booking?->service?->name),

                        Forms\Components\TextInput::make('review_period')
                            ->label('Review Period')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->created_at?->format('F Y')),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Customer Feedback')
                    ->schema([
                        Forms\Components\TextInput::make('overall_rating')
                            ->label('Overall Rating')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->overall_rating.'/5 ⭐')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('review')
                            ->label('Anonymous Customer Feedback')
                            ->disabled()
                            ->rows(6)
                            ->columnSpanFull(),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('review')
                    ->label('Customer Feedback')
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Year')
                    ->formatStateUsing(fn ($state) => $state->format('Y')) // Show only year
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year')
                    ->label('Year')
                    ->options(function () {
                        $years = [];
                        $currentYear = now()->year;
                        for ($i = 0; $i <= 5; $i++) {
                            $year = $currentYear - $i;
                            $years[$year] = $year;
                        }

                        return $years;
                    })
                    ->query(fn (Builder $query, array $data): Builder => $query->when($data['value'], fn ($query, $year) => $query->whereYear('created_at', $year)
                    )
                    ),
            ])
            ->actions([
                // No actions - reviews are read-only
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultGroup(null) // Ensure no default grouping
            ->poll('60s');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Technician\Resources\JobReportsResource\Pages\ListJobReports::route('/'),
            // View page removed - reviews are read-only list
        ];
    }
}
