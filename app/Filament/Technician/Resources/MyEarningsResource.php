<?php

namespace App\Filament\Technician\Resources;

use App\Models\Earning;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MyEarningsResource extends Resource
{
    protected static ?string $model = Earning::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationLabel = 'My Earnings';
    
    protected static ?string $title = 'My Earnings';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $technician = $user->technician;

        if (!$technician) {
            return parent::getEloquentQuery()->whereRaw('1 = 0'); // Return empty query
        }

        return parent::getEloquentQuery()
            ->where('technician_id', $technician->id)
            ->with(['booking.service', 'booking.customer', 'technician']);
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();
        $technician = $user->technician;

        if (!$technician) {
            return null;
        }

        $pendingEarnings = Earning::where('technician_id', $technician->id)
            ->where('payment_status', 'pending')
            ->count();

        return $pendingEarnings > 0 ? (string) $pendingEarnings : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
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

                        Forms\Components\TextInput::make('customer_name')
                            ->label('Customer')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->booking?->customer_name ?? $record->booking?->customer?->name),

                        Forms\Components\TextInput::make('job_date')
                            ->label('Job Date')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->booking?->scheduled_date?->format('M j, Y')),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Earning Breakdown')
                    ->schema([
                        Forms\Components\TextInput::make('base_amount')
                            ->label('Job Value')
                            ->prefix('₱')
                            ->disabled(),

                        Forms\Components\TextInput::make('commission_rate')
                            ->label('Commission Rate')
                            ->suffix('%')
                            ->disabled(),

                        Forms\Components\TextInput::make('commission_amount')
                            ->label('Commission Amount')
                            ->prefix('₱')
                            ->disabled(),

                        Forms\Components\TextInput::make('bonus_amount')
                            ->label('Performance Bonus')
                            ->prefix('₱')
                            ->disabled()
                            ->visible(fn ($record) => $record->bonus_amount > 0),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Earning')
                            ->prefix('₱')
                            ->disabled()
                            ->extraAttributes(['style' => 'font-weight: bold']),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Payment Status')
                    ->schema([
                        Forms\Components\Select::make('payment_status')
                            ->label('Payment Status')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'unpaid' => 'Unpaid',
                            ])
                            ->disabled(),

                        Forms\Components\TextInput::make('payment_date')
                            ->label('Paid Date')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->paid_at ? $record->paid_at->format('M j, Y g:i A') : null)
                            ->visible(fn ($record) => $record->paid_at),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking.booking_number')
                    ->label('Job #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('booking.service.name')
                    ->label('Service')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('base_amount')
                    ->label('Base')
                    ->money('PHP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('commission_rate')
                    ->label('Rate')
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->money('PHP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('PHP')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger' => 'cancelled',
                    ]),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid')
                    ->date()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\Filter::make('this_month')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('created_at', now()->month))
                    ->label('This Month'),

                Tables\Filters\Filter::make('last_30_days')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(30)))
                    ->label('Last 30 Days'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View'),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Technician\Resources\MyEarningsResource\Pages\ListMyEarnings::route('/'),
            'view' => \App\Filament\Technician\Resources\MyEarningsResource\Pages\ViewMyEarning::route('/{record}'),
        ];
    }
}
