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

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        $technicianId = $user->technician?->id;
        
        if (!$technicianId) {
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
        if (!$technicianId) {
            return parent::getEloquentQuery()->whereRaw('1 = 0'); // Return no results
        }
        
        return parent::getEloquentQuery()
            ->where('technician_id', $technicianId)
            ->with(['customer', 'service', 'airconType', 'timeslot']);
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
                        Forms\Components\TextInput::make('start_date')
                            ->label('Scheduled Date')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->scheduled_date ? $record->scheduled_date->format('M j, Y') : null),

                        Forms\Components\TextInput::make('time_slot')
                            ->label('Time Slot')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->timeslot?->display_time),

                        Forms\Components\TextInput::make('end_date')
                            ->label('Estimated End Date')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record->scheduled_end_date ? $record->scheduled_end_date->format('M j, Y') : null)
                            ->visible(fn ($record) => $record->scheduled_end_date),

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
                            ->visible(fn ($record) => !empty($record->admin_notes)),
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
            ->columns([
                Tables\Columns\TextColumn::make('booking_number')
                    ->label('Booking #')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('display_name')
                    ->label('Customer')
                    ->searchable(['customer_name', 'customer.name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label('Date')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('timeslot.display_time')
                    ->label('Time')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'confirmed', 
                        'primary' => 'in_progress',
                        'success' => 'completed',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer_address')
                    ->label('Address')
                    ->limit(30)
                    ->tooltip(fn($record) => $record->customer_address),

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
            ])
            ->actions([
                Tables\Actions\Action::make('start_job')
                    ->label('Start Job')
                    ->icon('heroicon-m-play')
                    ->color('success')
                    ->visible(fn(Booking $record): bool => $record->status === 'confirmed')
                    ->requiresConfirmation()
                    ->modalHeading('Start Job')
                    ->modalDescription('Mark this job as in progress?')
                    ->action(function (Booking $record): void {
                        $record->update(['status' => 'in_progress']);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Job Started')
                            ->body("Job {$record->booking_number} marked as in progress.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('complete_job')
                    ->label('Complete')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->visible(fn(Booking $record): bool => $record->status === 'in_progress')
                    ->requiresConfirmation()
                    ->modalHeading('Complete Job')
                    ->modalDescription('Mark this job as completed?')
                    ->action(function (Booking $record): void {
                        $record->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Job Completed')
                            ->body("Job {$record->booking_number} marked as completed.")
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
