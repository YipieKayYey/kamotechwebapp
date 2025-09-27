<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuestCustomerResource\Pages;
use App\Filament\Resources\GuestCustomerResource\RelationManagers;
use App\Models\GuestCustomer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GuestCustomerResource extends Resource
{
    protected static ?string $model = GuestCustomer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?string $navigationLabel = 'Guest Customers';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('first_name')
                                ->label('First Name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('middle_initial')
                                ->label('M.I.')
                                ->maxLength(5),
                            Forms\Components\TextInput::make('last_name')
                                ->label('Last Name')
                                ->required()
                                ->maxLength(255),
                        ])->columns(3),

                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('phone')
                                ->label('Phone Number')
                                ->tel()
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->maxLength(255)
                                ->helperText('Required for conversion to registered user'),
                        ])->columns(2),
                    ]),

                Forms\Components\Section::make('Address Information')
                    ->schema([
                        Forms\Components\TextInput::make('house_no_street')
                            ->label('House No. & Street')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('barangay')
                                ->label('Barangay')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('city_municipality')
                                ->label('City/Municipality')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('province')
                                ->label('Province')
                                ->maxLength(255),
                        ])->columns(3),

                        Forms\Components\TextInput::make('nearest_landmark')
                            ->label('Nearest Landmark')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('total_bookings')
                                ->label('Total Bookings')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(false),
                            Forms\Components\DateTimePicker::make('last_booking_date')
                                ->label('Last Booking')
                                ->disabled()
                                ->dehydrated(false),
                        ])->columns(2),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('full_address')
                    ->label('Address')
                    ->limit(50)
                    ->tooltip(fn (GuestCustomer $record): string => $record->full_address ?: 'No address')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_bookings')
                    ->label('Bookings')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('last_booking_date')
                    ->label('Last Booking')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(fn (GuestCustomer $record) => $record->converted_to_user_id ? 'Converted' : 'Guest')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Converted' => 'success',
                        'Guest' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'guest' => 'Guest Only',
                        'converted' => 'Converted to User',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'guest' => $query->whereNull('converted_to_user_id'),
                            'converted' => $query->whereNotNull('converted_to_user_id'),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('convert')
                    ->label('Convert to User')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->visible(fn (GuestCustomer $record): bool => $record->canConvertToUser())
                    ->requiresConfirmation()
                    ->modalHeading('Convert Guest to Registered User')
                    ->modalDescription('This will create a new user account and transfer all bookings.')
                    ->modalSubmitActionLabel('Convert')
                    ->form([
                        Forms\Components\TextInput::make('password')
                            ->label('Initial Password')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->helperText('Set a password for the new user account'),
                    ])
                    ->action(function (GuestCustomer $record, array $data): void {
                        $user = $record->convertToUser($data['password']);
                        if ($user) {
                            \Filament\Notifications\Notification::make()
                                ->title('Guest Converted Successfully')
                                ->body("Guest customer has been converted to user: {$user->email}")
                                ->success()
                                ->send();
                        }
                    }),
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
            RelationManagers\BookingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuestCustomers::route('/'),
            'create' => Pages\CreateGuestCustomer::route('/create'),
            'view' => Pages\ViewGuestCustomer::route('/{record}'),
            'edit' => Pages\EditGuestCustomer::route('/{record}/edit'),
        ];
    }
}
