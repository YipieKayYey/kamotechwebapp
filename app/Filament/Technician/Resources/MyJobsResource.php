<?php

namespace App\Filament\Technician\Resources;

use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MyJobsResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'My Jobs';

    protected static ?string $modelLabel = 'Job';

    protected static ?string $pluralModelLabel = 'My Jobs';

    protected static ?int $navigationSort = 1;

    protected static ?array $searchable = [
        'booking_number',
        'customer_address',
        'customer_mobile',
        'special_instructions',
    ];

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        $technicianId = $user->technician?->id;

        if (! $technicianId) {
            return null;
        }

        $count = static::getModel()::where('technician_id', $technicianId)
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

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
            ->whereIn('status', ['pending', 'confirmed', 'in_progress', 'cancel_requested'])
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

                        Forms\Components\TextInput::make('duration')
                            ->label('Duration')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->estimated_duration_minutes ? ($record->estimated_duration_minutes / 60).' hours' : null)
                            ->visible(fn ($record) => $record->estimated_duration_minutes),

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
                            ->label('Current Status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                            ])
                            ->disabled(),

                        Forms\Components\TextInput::make('completion_date')
                            ->label('Completed At')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->completed_at ? $record->completed_at->format('M j, Y g:i A') : null)
                            ->visible(fn ($record) => $record->completed_at),
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
                    ->label('Start Date/Time')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('scheduled_end_at')
                    ->label('Est. End Date/Time')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(function ($state, $record) {
                        if ($state === 'confirmed' &&
                            $record->scheduled_start_at &&
                            now()->greaterThanOrEqualTo($record->scheduled_start_at->startOfDay())) {
                            return 'Ready to Start';
                        }

                        return ucfirst(str_replace('_', ' ', $state));
                    })
                    ->colors([
                        'warning' => fn ($state, $record) => $state === 'pending' ||
                            ($state === 'confirmed' &&
                             $record->scheduled_start_at &&
                             now()->lessThan($record->scheduled_start_at->startOfDay())),
                        'success' => fn ($state, $record) => $state === 'completed' ||
                            ($state === 'confirmed' &&
                             $record->scheduled_start_at &&
                             now()->greaterThanOrEqualTo($record->scheduled_start_at->startOfDay())),
                        'primary' => fn ($state) => $state === 'in_progress',
                        'danger' => fn ($state) => $state === 'cancelled',
                        'gray' => fn ($state) => $state === 'cancel_requested',
                    ])
                    ->icon(fn ($state, $record) => match (true) {
                        $state === 'confirmed' &&
                        $record->scheduled_start_at &&
                        now()->greaterThanOrEqualTo($record->scheduled_start_at->startOfDay()) => 'heroicon-m-play-circle',
                        $state === 'in_progress' => 'heroicon-m-arrow-path',
                        $state === 'completed' => 'heroicon-m-check-circle',
                        $state === 'cancelled' => 'heroicon-m-x-circle',
                        default => null,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer_address')
                    ->label('Address')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->customer_address)
                    ->searchable(),

                Tables\Columns\TextColumn::make('contact_number')
                    ->label('Contact')
                    ->getStateUsing(fn ($record) => $record->customer_mobile ?? $record->customer->phone ?? 'No contact')
                    ->icon('heroicon-m-phone')
                    ->copyable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                    ]),

                Tables\Filters\SelectFilter::make('service')
                    ->label('Service')
                    ->relationship('service', 'name')
                    ->preload()
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\Action::make('start_job')
                    ->label('Start Job')
                    ->icon('heroicon-m-play')
                    ->color('success')
                    ->visible(fn (Booking $record): bool => $record->status === 'confirmed' &&
                        $record->scheduled_start_at &&
                        now()->greaterThanOrEqualTo($record->scheduled_start_at->startOfDay())
                    )
                    ->tooltip(function (Booking $record): ?string {
                        if ($record->status !== 'confirmed') {
                            return null;
                        }
                        if ($record->scheduled_start_at && now()->lessThan($record->scheduled_start_at->startOfDay())) {
                            return 'Job can be started on '.$record->scheduled_start_at->format('M j, Y');
                        }

                        return 'Click to start this job';
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Start Job')
                    ->modalDescription(fn (Booking $record) => "Start job {$record->booking_number} for {$record->service->name}?")
                    ->modalSubmitActionLabel('Yes, Start Job')
                    ->action(function (Booking $record): void {
                        $record->update([
                            'status' => 'in_progress',
                            'started_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Job Started Successfully')
                            ->body("Job {$record->booking_number} is now in progress.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('complete_job')
                    ->label('Complete Job')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->visible(fn (Booking $record): bool => $record->status === 'in_progress')
                    ->requiresConfirmation()
                    ->modalHeading('Complete Job')
                    ->modalDescription(fn (Booking $record) => "Mark job {$record->booking_number} as completed?")
                    ->modalSubmitActionLabel('Yes, Complete Job')
                    ->action(function (Booking $record): void {
                        $record->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Job Completed Successfully')
                            ->body("Job {$record->booking_number} has been marked as completed.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // No bulk actions for technicians
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Technician\Resources\MyJobsResource\Pages\ListMyJobs::route('/'),
            'view' => \App\Filament\Technician\Resources\MyJobsResource\Pages\ViewMyJob::route('/{record}'),
            'edit' => \App\Filament\Technician\Resources\MyJobsResource\Pages\EditMyJob::route('/{record}/edit'),
        ];
    }
}
