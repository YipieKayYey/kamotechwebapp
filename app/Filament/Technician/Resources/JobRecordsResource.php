<?php

namespace App\Filament\Technician\Resources;

use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class JobRecordsResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Job Records';

    protected static ?string $modelLabel = 'Job Record';

    protected static ?string $pluralModelLabel = 'Job Records';

    protected static ?int $navigationSort = 2;

    protected static ?array $searchable = [
        'booking_number',
        'customer_address',
        'customer_mobile',
        'special_instructions',
    ];

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $technicianId = $user->technician?->id;

        // If no technician record exists, return empty query
        if (! $technicianId) {
            return parent::getEloquentQuery()->whereRaw('1 = 0'); // Return no results
        }

        return parent::getEloquentQuery()
            ->where('technician_id', $technicianId)
            ->whereIn('status', ['completed', 'cancelled'])
            ->with(['customer', 'service', 'airconType']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Job Information')
                    ->schema([
                        Forms\Components\TextInput::make('booking_number')
                            ->label('Booking #')
                            ->disabled(),

                        Forms\Components\TextInput::make('customer_name')
                            ->label('Customer Name')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->customer_name ?? $record->customer?->name),

                        Forms\Components\TextInput::make('service_name')
                            ->label('Service Type')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->service?->name),

                        Forms\Components\TextInput::make('aircon_type')
                            ->label('Aircon Type')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->airconType?->name ?? 'Not specified'),

                        Forms\Components\TextInput::make('units')
                            ->label('Number of Units')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->number_of_units ?? 1),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Job Value')
                            ->prefix('₱')
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make('Schedule & Location')
                    ->schema([
                        Forms\Components\TextInput::make('scheduled_start')
                            ->label('Scheduled Start')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->scheduled_start_at ? $record->scheduled_start_at->format('M j, Y g:i A') : null),

                        Forms\Components\TextInput::make('scheduled_end')
                            ->label('Scheduled End')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->scheduled_end_at ? $record->scheduled_end_at->format('M j, Y g:i A') : null),

                        Forms\Components\TextInput::make('completion_date')
                            ->label('Completed At')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record && $record->completed_at ? $record->completed_at->format('M j, Y g:i A') : null)
                            ->visible(fn ($record) => $record && $record->status === 'completed'),

                        Forms\Components\Textarea::make('customer_address')
                            ->label('Service Address')
                            ->disabled()
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('nearest_landmark')
                            ->label('Nearest Landmark')
                            ->disabled()
                            ->columnSpanFull()
                            ->placeholder('No landmark specified'),
                    ])->columns(3),

                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('customer_mobile')
                            ->label('Contact Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->customer_mobile ?? $record->customer?->phone ?? 'No contact'),

                        Forms\Components\TextInput::make('customer_email')
                            ->label('Customer Email')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->customer?->email),
                    ])->columns(2),

                Forms\Components\Section::make('Service Instructions')
                    ->schema([
                        Forms\Components\Textarea::make('special_instructions')
                            ->label('Special Instructions from Customer')
                            ->disabled()
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('No special instructions provided'),

                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Admin Notes')
                            ->disabled()
                            ->rows(2)
                            ->columnSpanFull()
                            ->visible(fn ($record) => ! empty($record->admin_notes)),
                    ]),

                Forms\Components\Section::make('Job Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Final Status')
                            ->options([
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->disabled(),

                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->disabled()
                            ->rows(2)
                            ->visible(fn ($record) => $record && $record->status === 'cancelled' && ! empty($record->cancellation_reason)),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->searchable()
            ->searchPlaceholder('Search by booking #, customer, service, or address...')
            ->modifyQueryUsing(function (Builder $query) {
                return $query->with(['customer', 'service', 'airconType']);
            })
            ->columns([
                Tables\Columns\TextColumn::make('booking_number')
                    ->label('Booking #')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('display_name')
                    ->label('Customer')
                    ->getStateUsing(fn ($record) => $record->customer_name ?? $record->customer?->name ?? 'Guest')
                    ->sortable(false)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function ($query) use ($search) {
                            $query->whereHas('customer', function ($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%");
                            });
                        });
                    }),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('service', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('scheduled_start_at')
                    ->label('Service Date')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed Date')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->visible(fn ($record) => $record && $record->status === 'completed')
                    ->default('—'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->colors([
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('PHP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer_address')
                    ->label('Address')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->customer_address)
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('service')
                    ->label('Service')
                    ->relationship('service', 'name')
                    ->preload()
                    ->searchable(),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('scheduled_start_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('scheduled_start_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for job records
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Technician\Resources\JobRecordsResource\Pages\ListJobRecords::route('/'),
            'view' => \App\Filament\Technician\Resources\JobRecordsResource\Pages\ViewJobRecord::route('/{record}'),
        ];
    }
}
