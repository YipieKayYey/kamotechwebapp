<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?string $navigationLabel = 'Users';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->label('First Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('middle_initial')
                            ->label('Middle Initial')
                            ->maxLength(5),
                        Forms\Components\TextInput::make('last_name')
                            ->label('Last Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('date_of_birth')
                            ->label('Date of Birth')
                            ->displayFormat('M d, Y')
                            ->maxDate(now()->subYears(18))
                            ->required()
                            ->placeholder('Select date of birth'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->required()
                            ->maxLength(255)
                            ->placeholder('+63 917 123 4567'),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required()
                            ->maxLength(255)
                            ->visibleOn('create'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Address Information')
                    ->schema([
                        Forms\Components\Select::make('province')
                            ->label('Province')
                            ->searchable()
                            ->preload()
                            ->options(fn () => \App\Models\Province::orderBy('name')->pluck('name', 'id')->toArray())
                            ->reactive()
                            ->required(),
                        Forms\Components\Select::make('city_municipality')
                            ->label('City/Municipality')
                            ->searchable()
                            ->preload()
                            ->options(function (callable $get) {
                                $provinceId = $get('province');
                                if (! $provinceId) { return []; }
                                return \App\Models\City::where('province_id', $provinceId)->orderBy('name')->pluck('name', 'id')->toArray();
                            })
                            ->reactive()
                            ->required(),
                        Forms\Components\Select::make('barangay')
                            ->label('Barangay')
                            ->searchable()
                            ->preload()
                            ->options(function (callable $get) {
                                $cityId = $get('city_municipality');
                                if (! $cityId) { return []; }
                                return \App\Models\Barangay::where('city_id', $cityId)->orderBy('name')->pluck('name', 'id')->toArray();
                            })
                            ->required(),
                        Forms\Components\TextInput::make('house_no_street')
                            ->label('House No. & Street')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Account Settings')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->options([
                                'technician' => 'Technician',
                                'admin' => 'Admin',
                            ])
                            ->required()
                            ->default('technician')
                            ->helperText('Admin panel is for internal staff only. Customers register through the website.'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Account Active')
                            ->default(true),
                        Forms\Components\FileUpload::make('avatar')
                            ->label('Profile Picture')
                            ->image()
                            ->directory('avatars')
                            ->visibility('public'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->getStateUsing(fn ($record) => $record->full_name)
                    ->searchable(query: function ($query, string $search): void {
                        $query->where(function ($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                                ->orWhere('middle_initial', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function ($query, string $direction): void {
                        $query->orderBy('last_name', $direction)
                            ->orderBy('first_name', $direction);
                    }),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('full_address')
                    ->label('Address')
                    ->getStateUsing(fn ($record) => $record->full_address)
                    ->wrap()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->full_address),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'technician' => 'warning',
                        'customer' => 'success',
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('date_of_birth')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Filter by Role')
                    ->options([
                        'admin' => 'Admin',
                        'technician' => 'Technician',
                        'customer' => 'Customer',
                    ]),
            ])
            ->actions([
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
