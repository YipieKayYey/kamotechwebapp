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
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required()
                            ->maxLength(255)
                            ->visibleOn('create'),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255)
                            ->placeholder('09123456789'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Contact & Address')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->label('Legacy Address (Optional)')
                            ->rows(2)
                            ->placeholder('For backward compatibility - leave empty if using structured address')
                            ->helperText('Use structured address fields below for new/updated entries')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('province')
                            ->label('Province')
                            ->placeholder('e.g., Bataan')
                            ->helperText('Enter province name'),

                        Forms\Components\TextInput::make('city_municipality')
                            ->label('City/Municipality')
                            ->placeholder('e.g., Balanga City, Hermosa')
                            ->helperText('Enter city or municipality name'),

                        Forms\Components\TextInput::make('barangay')
                            ->label('Barangay')
                            ->placeholder('e.g., Central, Poblacion')
                            ->helperText('Enter barangay name'),

                        Forms\Components\TextInput::make('house_no_street')
                            ->label('House No. & Street')
                            ->placeholder('e.g., 123 Rizal Street')
                            ->helperText('Enter house number and street name')
                            ->columnSpanFull(),

                    ])
                    ->columns(3),

                Forms\Components\Section::make('Account Settings')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->options([
                                'customer' => 'Customer',
                                'technician' => 'Technician',
                                'admin' => 'Admin',
                            ])
                            ->required()
                            ->default('customer'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Account Active')
                            ->default(true),
                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('Email Verified At')
                            ->displayFormat('M d, Y H:i'),
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
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('formatted_address')
                    ->label('Address')
                    ->searchable(['address', 'province', 'city_municipality', 'barangay', 'house_no_street'])
                    ->sortable()
                    ->limit(40)
                    ->tooltip(function ($record) {
                        return $record->formatted_address;
                    }),
                Tables\Columns\TextColumn::make('city_municipality')
                    ->label('City')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('role'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('avatar')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
