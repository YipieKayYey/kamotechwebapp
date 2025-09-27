<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TechnicianResource\Pages;
use App\Filament\Resources\TechnicianResource\RelationManagers;
use App\Filament\Resources\TechnicianResource\Tables\TechniciansTable;
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
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;

class TechnicianResource extends Resource
{
    protected static ?string $model = Technician::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationGroup = 'Technician Management';
    protected static ?string $navigationLabel = 'Technicians';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $highCommissionCount = static::getModel()::where('commission_rate', '>=', 20)->count();
        return $highCommissionCount > 0 ? $highCommissionCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getNavigationBadgeTooltip(): ?string 
    {
        $highCommissionCount = static::getModel()::where('commission_rate', '>=', 20)->count();
        return $highCommissionCount > 0 ? "{$highCommissionCount} technician(s) with premium commission (20%+)" : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Account Information')
                    ->description('Select a user account for this technician')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Select Technician User')
                            ->relationship('user', 'name', function ($query) {
                                return $query->where('role', 'technician');
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Choose a technician user')
                            ->helperText('Select a user with technician role')
                            ->required()
                            ->columnSpanFull(),
                            
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('create_user')
                                ->label('Create New Technician User')
                                ->icon('heroicon-o-plus')
                                ->url(fn() => route('filament.admin.resources.users.create'))
                                ->color('primary'),
                        ])
                            ->fullWidth()
                            ->alignment('start'),
                    ]),
                    
                Forms\Components\Section::make('Employment Details')
                    ->schema([
                        Forms\Components\TextInput::make('employee_id')
                            ->label('Employee ID')
                            ->required()
                            ->maxLength(255)
                            ->default(function () {
                                // Auto-generate employee ID following KMT-XXX pattern
                                $lastTechnician = \App\Models\Technician::orderBy('id', 'desc')->first();
                                $nextNumber = $lastTechnician ? 
                                    ((int) str_replace('KMT-', '', $lastTechnician->employee_id)) + 1 : 1;
                                return 'KMT-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
                            })
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Auto-generated unique employee identifier (KMT-XXX)'),
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
                            ->minValue(0)
                            ->maxValue(5)
                            ->step(0.1)
                            ->default(0.00)
                            ->helperText('Starting rating (0-5 stars, will increase with reviews)'),
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
                    
                Forms\Components\Section::make('💰 Commission & Financial Settings')
                    ->description('Manage technician commission rates and financial tracking')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('commission_rate')
                                ->label('Commission Rate (%)')
                                ->required()
                                ->numeric()
                                ->suffix('%')
                                ->step(0.01)
                                ->minValue(5.00)
                                ->maxValue(30.00)
                                ->default(10.00)
                                ->helperText('Commission percentage on completed bookings (5% - 30%)')
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    // Update commission examples in real-time
                                    $rate = $state ?? 10;
                                    $commission1 = 1000 * ($rate / 100);
                                    $commission2 = 2500 * ($rate / 100);
                                    $set('commission_examples', "₱1000 → ₱" . number_format($commission1, 0) . " | ₱2500 → ₱" . number_format($commission2, 0));
                                }),

                            Forms\Components\TextInput::make('commission_examples')
                                ->label('Commission Examples')
                                ->disabled()
                                ->dehydrated(false)
                                ->default(function (callable $get): string {
                                    $rate = $get('commission_rate') ?? 10;
                                    $commission1 = 1000 * ($rate / 100);
                                    $commission2 = 2500 * ($rate / 100);
                                    return "₱1000 → ₱" . number_format($commission1, 0) . " | ₱2500 → ₱" . number_format($commission2, 0);
                                })
                                ->helperText('Real-time calculation based on commission rate'),
                        ])->columns(2),

                        Forms\Components\Group::make([
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
                        ])->columns(2),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TechniciansTable::configure($table);
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
