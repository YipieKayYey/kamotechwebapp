<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PendingBookingResource\Pages;
use App\Models\Booking;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PendingBookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Booking Management';

    protected static ?string $navigationLabel = 'Pending Bookings';

    protected static ?string $modelLabel = 'Pending Booking';

    protected static ?string $pluralModelLabel = 'Pending Bookings';

    protected static ?int $navigationSort = 1; // Show before regular Booking Management

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::where('status', 'pending')->count();

        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $pendingCount = static::getModel()::where('status', 'pending')->count();

        return $pendingCount > 0 ? "{$pendingCount} pending booking(s) awaiting confirmation" : null;
    }

    // Use the same form structure as BookingResource
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->description('Core booking details')
                    ->schema([
                        Forms\Components\TextInput::make('booking_number')
                            ->label('Booking Number')
                            ->disabled()
                            ->placeholder('Auto-generated'),

                        Forms\Components\Select::make('customer_id')
                            ->label('Registered Customer')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn ($record) => $record?->customer_id !== null)
                            ->disabled(),

                        Forms\Components\Select::make('guest_customer_id')
                            ->label('Guest Customer')
                            ->relationship('guestCustomer', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name.' - '.$record->phone)
                            ->searchable()
                            ->preload()
                            ->visible(fn ($record) => $record?->guest_customer_id !== null)
                            ->disabled(),

                        Forms\Components\TextInput::make('customer_name')
                            ->label('Guest Customer Name (Legacy)')
                            ->placeholder('For old walk-in/phone bookings')
                            ->visible(fn ($record) => $record?->customer_id === null && $record?->guest_customer_id === null && $record?->customer_name !== null)
                            ->disabled(),

                        Forms\Components\Select::make('service_id')
                            ->label('Service')
                            ->relationship('service', 'name')
                            ->required(),

                        Forms\Components\Select::make('aircon_type_id')
                            ->label('AC Type')
                            ->relationship('airconType', 'name'),

                        Forms\Components\TextInput::make('number_of_units')
                            ->label('Units')
                            ->numeric()
                            ->required()
                            ->minValue(1),

                        Forms\Components\Select::make('technician_id')
                            ->label('Technician')
                            ->relationship('technician', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user->name)
                            ->searchable(),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Amount (₱)')
                            ->numeric()
                            ->prefix('₱')
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Schedule')
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_start_at')
                            ->label('Start')
                            ->seconds(false)
                            ->required(),

                        Forms\Components\DateTimePicker::make('scheduled_end_at')
                            ->label('End')
                            ->seconds(false)
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                            ])
                            ->default('pending')
                            ->required(),

                        Forms\Components\Select::make('payment_status')
                            ->label('Payment')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'unpaid' => 'Unpaid',
                            ])
                            ->required(),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Address & Contact')
                    ->schema([
                        Forms\Components\TextInput::make('customer_mobile')
                            ->label('Mobile')
                            ->tel(),

                        // Toggle for using customer's registered address
                        Forms\Components\Toggle::make('use_customer_address')
                            ->label('Use Customer\'s Registered Address')
                            ->helperText('Toggle to use the customer\'s saved address or enter a custom address')
                            ->reactive()
                            ->dehydrated(false)
                            ->default(false)
                            ->visible(fn (Forms\Get $get) => $get('customer_id') !== null)
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                if ($state && $get('customer_id')) {
                                    $customer = User::find($get('customer_id'));
                                    if ($customer) {
                                        $set('province', $customer->province ?? '');
                                        $set('city_municipality', $customer->city_municipality ?? '');
                                        $set('barangay', $customer->barangay ?? '');
                                        $set('house_no_street', $customer->house_no_street ?? '');
                                        $set('nearest_landmark', $customer->nearest_landmark ?? '');
                                    }
                                }
                            })
                            ->columnSpanFull(),

                        // Province select (stores name into `province` on save)
                        Forms\Components\Select::make('province')
                            ->label('Province')
                            ->searchable()
                            ->options(fn (): array => \App\Models\Province::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->getOptionLabelUsing(fn ($value): ?string => \App\Models\Province::find($value)?->name)
                            ->dehydrateStateUsing(fn ($state): ?string => \App\Models\Province::find($state)?->name)
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Set $set): void {
                                $set('city_municipality', null);
                                $set('barangay', null);
                            })
                            ->required()
                            ->disabled(fn (Forms\Get $get) => $get('use_customer_address')),

                        // City/Municipality select (stores name into `city_municipality` on save)
                        Forms\Components\Select::make('city_municipality')
                            ->label('City/Municipality')
                            ->searchable()
                            ->options(function (Forms\Get $get): array {
                                $provinceId = $get('province');
                                if (! $provinceId) {
                                    return [];
                                }

                                return \App\Models\City::query()
                                    ->where('province_id', $provinceId)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->getOptionLabelUsing(fn ($value): ?string => \App\Models\City::find($value)?->name)
                            ->dehydrateStateUsing(fn ($state): ?string => \App\Models\City::find($state)?->name)
                            ->reactive()
                            ->disabled(fn (Forms\Get $get): bool => ! (bool) $get('province'))
                            ->afterStateUpdated(function (Forms\Set $set): void {
                                $set('barangay', null);
                            })
                            ->required()
                            ->disabled(fn (Forms\Get $get) => $get('use_customer_address')),

                        // Barangay select (stores name into `barangay` on save)
                        Forms\Components\Select::make('barangay')
                            ->label('Barangay')
                            ->searchable()
                            ->options(function (Forms\Get $get): array {
                                $cityId = $get('city_municipality');
                                if (! $cityId) {
                                    return [];
                                }

                                return \App\Models\Barangay::query()
                                    ->where('city_id', $cityId)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->getOptionLabelUsing(fn ($value): ?string => \App\Models\Barangay::find($value)?->name)
                            ->dehydrateStateUsing(fn ($state): ?string => \App\Models\Barangay::find($state)?->name)
                            ->reactive()
                            ->disabled(fn (Forms\Get $get): bool => $get('use_customer_address') || ! (bool) $get('city_municipality'))
                            ->required(),

                        Forms\Components\TextInput::make('house_no_street')
                            ->label('House No. & Street')
                            ->disabled(fn (Forms\Get $get) => $get('use_customer_address'))
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('nearest_landmark')
                            ->label('Nearest Landmark')
                            ->disabled(fn (Forms\Get $get) => $get('use_customer_address'))
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('special_instructions')
                            ->label('Special Instructions')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->defaultSort('created_at', 'desc')
            ->searchable()
            ->searchPlaceholder('Search by booking #, customer, service...')
            ->modifyQueryUsing(function (Builder $query) {
                // IMPORTANT: Filter to show only pending bookings
                return $query->where('status', 'pending')
                    ->with(['customer', 'service', 'technician.user', 'airconType']);
            })
            ->filters([
                // Remove status filter since we're only showing pending
                Tables\Filters\SelectFilter::make('payment_status')->options([
                    'pending' => 'Pending',
                    'paid' => 'Paid',
                    'unpaid' => 'Unpaid',
                ]),
            ])
            ->actions(static::getTableActions())
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Delete actions disabled as per panelist requirement
                ]),
            ]);
    }

    protected static function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('booking_number')->label('Booking #')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('display_name')
                ->label('Customer')
                ->getStateUsing(fn ($record) => $record->display_name)
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where(function ($query) use ($search) {
                        $query->whereHas('customer', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    });
                })
                ->sortable(),
            Tables\Columns\TextColumn::make('service.name')
                ->label('Service')
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->whereHas('service', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
                })
                ->sortable(),
            Tables\Columns\TextColumn::make('number_of_units')->label('Units')->suffix(' unit(s)')->sortable()->alignCenter(),
            Tables\Columns\TextColumn::make('technician.user.name')
                ->label('Technician')
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->whereHas('technician.user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
                })
                ->sortable(),
            Tables\Columns\TextColumn::make('scheduled_start_at')->label('Start')->dateTime('M j, Y g:i A')->sortable(),
            Tables\Columns\TextColumn::make('scheduled_end_at')->label('End')->dateTime('M j, Y g:i A')->sortable(),
            Tables\Columns\BadgeColumn::make('status')
                ->label('Status')
                ->colors([
                    'warning' => 'pending',
                    'info' => ['confirmed', 'cancel_requested'],
                    'primary' => 'in_progress',
                    'success' => 'completed',
                    'danger' => 'cancelled',
                ])
                ->sortable(),
            Tables\Columns\TextColumn::make('created_at')->label('Booked On')->dateTime('M j, Y g:i A')->sortable(),
        ];
    }

    protected static function getTableActions(): array
    {
        return [
            // Regular booking actions - same as BookingResource but only for pending
            Tables\Actions\Action::make('confirm')
                ->label('Confirm')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->visible(fn (Booking $record): bool => $record->status === 'pending')
                ->requiresConfirmation()
                ->action(function (Booking $record): void {
                    $record->update([
                        'status' => 'confirmed',
                        'confirmed_at' => now(),
                        'confirmed_by' => auth()->id(),
                    ]);

                    // Send SMS notification
                    $smsService = new \App\Services\SemaphoreSmsService;
                    $smsService->sendBookingConfirmation($record);
                }),

            Tables\Actions\Action::make('cancel')
                ->label('Cancel')
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->visible(fn (Booking $record): bool => in_array($record->status, ['pending', 'confirmed']))
                ->requiresConfirmation()
                ->modalHeading('Cancel Booking')
                ->modalDescription('This will mark the booking as cancelled and set payment status to unpaid. This action cannot be undone.')
                ->action(function (Booking $record): void {
                    $record->update([
                        'status' => 'cancelled',
                        'payment_status' => 'unpaid',
                        'cancellation_processed_at' => now(),
                        'cancellation_processed_by' => auth()->id(),
                    ]);

                    // Send SMS notification
                    $smsService = new \App\Services\SemaphoreSmsService;
                    $smsSent = $smsService->sendBookingCancellation($record);

                    $notification = \Filament\Notifications\Notification::make()
                        ->title('Booking Cancelled')
                        ->body("Booking {$record->booking_number} has been cancelled.");

                    if ($smsSent) {
                        $notification->body("Booking {$record->booking_number} has been cancelled. Customer notified via SMS.");
                    }

                    $notification->danger()->send();
                }),
            Tables\Actions\Action::make('complete')->label('Complete')->icon('heroicon-m-check-badge')->color('success')->visible(fn (Booking $record): bool => $record->status === 'in_progress')->requiresConfirmation()->action(function (Booking $record): void {
                $record->update(['status' => 'completed', 'completed_at' => now()]);
            }),
            ViewAction::make(),
            EditAction::make()->visible(fn (Booking $record): bool => ! in_array($record->status, ['cancelled', 'completed'])),
        ];
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
            'index' => Pages\ListPendingBookings::route('/'),
            'view' => Pages\ViewPendingBooking::route('/{record}'),
            'edit' => Pages\EditPendingBooking::route('/{record}/edit'),
        ];
    }
}
