<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TechnicianResource\Pages;
use App\Filament\Resources\TechnicianResource\RelationManagers;
use App\Models\Technician;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TechnicianResource extends Resource
{
    protected static ?string $model = Technician::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationGroup = 'Technician Management';
    protected static ?string $navigationLabel = 'Technicians';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Account Information')
                    ->description('Create or select a user account for this technician')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Existing User (Optional)')
                            ->relationship('user', 'name', function ($query) {
                                return $query->where('role', 'technician')->orWhere('role', 'admin');
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Select existing user OR fill out new user details below')
                            ->helperText('Leave empty to create a new user account')
                            ->reactive()
                            ->columnSpanFull(),
                            
                        // New User Creation Fields (shown when no existing user selected)
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('user.name')
                                ->label('Full Name')
                                ->required(fn (callable $get) => !$get('user_id'))
                                ->maxLength(255)
                                ->placeholder('Juan Dela Cruz')
                                ->hidden(fn (callable $get) => filled($get('user_id'))),
                            Forms\Components\TextInput::make('user.email')
                                ->label('Email')
                                ->email()
                                ->required(fn (callable $get) => !$get('user_id'))
                                ->maxLength(255)
                                ->placeholder('juan@example.com')
                                ->hidden(fn (callable $get) => filled($get('user_id'))),
                            Forms\Components\TextInput::make('user.password')
                                ->label('Password')
                                ->password()
                                ->required(fn (callable $get) => !$get('user_id'))
                                ->maxLength(255)
                                ->placeholder('Create a secure password')
                                ->hidden(fn (callable $get) => filled($get('user_id'))),
                            Forms\Components\TextInput::make('user.phone')
                                ->label('Phone')
                                ->tel()
                                ->maxLength(255)
                                ->placeholder('09123456789')
                                ->hidden(fn (callable $get) => filled($get('user_id'))),
                        ])->columns(2),
                        
                        Forms\Components\Group::make([
                            Forms\Components\Textarea::make('user.address')
                                ->label('Address')
                                ->rows(2)
                                ->placeholder('Complete address (Street, Barangay, City, Province)')
                                ->columnSpanFull()
                                ->hidden(fn (callable $get) => filled($get('user_id'))),
                        ]),
                    ]),
                    
                Forms\Components\Section::make('Employment Details')
                    ->schema([
                        Forms\Components\TextInput::make('employee_id')
                            ->label('Employee ID')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('TECH-001')
                            ->helperText('Unique employee identifier'),
                        Forms\Components\DatePicker::make('hire_date')
                            ->label('Hire Date')
                            ->required()
                            ->default(now()),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Performance & Availability')
                    ->schema([
                        Forms\Components\TextInput::make('rating_average')
                            ->label('Initial Rating')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->step(0.1)
                            ->default(5.00)
                            ->helperText('Starting rating (1-5 stars)'),
                        Forms\Components\Toggle::make('is_available')
                            ->label('Currently Available')
                            ->default(true)
                            ->helperText('Can this technician receive new job assignments?'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Work Capacity')
                    ->schema([
                        Forms\Components\TextInput::make('max_daily_jobs')
                            ->label('Max Daily Jobs')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(5)
                            ->helperText('Maximum jobs this technician can handle per day'),
                    ])
                    ->columns(1),
                    
                Forms\Components\Section::make('Internal Management (Admin Only)')
                    ->description('Commission and performance tracking - internal use only')
                    ->schema([
                        Forms\Components\TextInput::make('commission_rate')
                            ->label('Commission Rate (%)')
                            ->required()
                            ->numeric()
                            ->suffix('%')
                            ->step(0.01)
                            ->default(15.00)
                            ->helperText('Percentage commission on completed jobs - keep confidential'),
                        Forms\Components\TextInput::make('total_jobs')
                            ->label('Total Jobs Completed')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->helperText('Automatically updated when jobs are completed'),
                        Forms\Components\TextInput::make('current_jobs')
                            ->label('Current Active Jobs')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->helperText('Automatically updated when jobs are assigned'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Technician Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee_id')
                    ->label('Employee ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_available')
                    ->label('Available')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rating_average')
                    ->label('Rating')
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->suffix('/5'),
                Tables\Columns\TextColumn::make('total_jobs')
                    ->label('Total Jobs')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_jobs')
                    ->label('Active Jobs')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('hire_date')
                    ->label('Hire Date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('commission_rate')
                    ->label('Commission (%)')
                    ->numeric(decimalPlaces: 1)
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('max_daily_jobs')
                    ->label('Max Daily Jobs')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListTechnicians::route('/'),
            'create' => Pages\CreateTechnician::route('/create'),
            'edit' => Pages\EditTechnician::route('/{record}/edit'),
        ];
    }
}
