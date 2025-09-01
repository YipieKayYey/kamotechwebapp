<?php

namespace App\Filament\Resources\TechnicianResource\Tables;

use App\Models\Technician;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TechniciansTable
{
    public static function configure(Table $table): Table
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
                    ->color(fn ($record) => match (true) {
                        $record->commission_rate >= 20 => 'success',
                        $record->commission_rate >= 15 => 'warning', 
                        default => 'gray'
                    })
                    ->badge(),
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
            ->filters(static::getFilters())
            ->actions(static::getActions())
            ->bulkActions(static::getBulkActions());
    }

    protected static function getFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('commission_tier')
                ->label('Commission Tier')
                ->options([
                    'low' => 'Low (5-10%)',
                    'standard' => 'Standard (10-15%)',
                    'high' => 'High (15-20%)',
                    'premium' => 'Premium (20%+)',
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query->when($data['value'], function (Builder $query, string $tier): Builder {
                        return match ($tier) {
                            'low' => $query->whereBetween('commission_rate', [5, 10]),
                            'standard' => $query->whereBetween('commission_rate', [10, 15]),
                            'high' => $query->whereBetween('commission_rate', [15, 20]),
                            'premium' => $query->where('commission_rate', '>=', 20),
                            default => $query,
                        };
                    });
                }),
                
            Tables\Filters\Filter::make('is_available')
                ->label('Available Only')
                ->query(fn (Builder $query): Builder => $query->where('is_available', true))
                ->toggle(),
                
            Tables\Filters\Filter::make('high_performers')
                ->label('High Performers')
                ->query(fn (Builder $query): Builder => $query->where('rating_average', '>=', 4.5))
                ->toggle(),
                
            Tables\Filters\Filter::make('experienced')
                ->label('Experienced (10+ Jobs)')
                ->query(fn (Builder $query): Builder => $query->where('total_jobs', '>=', 10))
                ->toggle(),
        ];
    }

    protected static function getActions(): array
    {
        return [
            Tables\Actions\Action::make('adjust_commission')
                ->label('Commission')
                ->icon('heroicon-m-currency-dollar')
                ->color('warning')
                ->form([
                    Forms\Components\TextInput::make('new_commission_rate')
                        ->label('New Commission Rate (%)')
                        ->required()
                        ->numeric()
                        ->suffix('%')
                        ->step(0.1)
                        ->minValue(5.0)
                        ->maxValue(30.0)
                        ->default(fn ($record) => $record->commission_rate)
                        ->helperText('Enter new commission percentage (5% - 30%)'),
                        
                    Forms\Components\Textarea::make('commission_reason')
                        ->label('Reason for Change')
                        ->placeholder('e.g., Performance bonus, rate adjustment, promotion')
                        ->rows(2)
                        ->helperText('Internal notes about this commission change'),
                ])
                ->action(function (array $data, $record): void {
                    $oldRate = $record->commission_rate;
                    $record->update([
                        'commission_rate' => $data['new_commission_rate']
                    ]);
                    
                    \Log::info('Commission rate updated', [
                        'technician_id' => $record->id,
                        'technician_name' => $record->user->name,
                        'old_commission_rate' => $oldRate,
                        'new_commission_rate' => $data['new_commission_rate'],
                        'reason' => $data['commission_reason'] ?? 'No reason provided',
                        'changed_by' => auth()->user()->name,
                        'changed_at' => now(),
                    ]);
                        
                    \Filament\Notifications\Notification::make()
                        ->title('Commission Updated')
                        ->body("Commission rate changed from {$oldRate}% to {$data['new_commission_rate']}% for {$record->user->name}")
                        ->success()
                        ->send();
                }),
                
            Tables\Actions\EditAction::make(),
        ];
    }

    protected static function getBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\BulkAction::make('bulk_commission_update')
                    ->label('Update Commission Rates')
                    ->icon('heroicon-m-currency-dollar')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('update_type')
                            ->label('Update Type')
                            ->options([
                                'set_rate' => 'Set Specific Rate',
                                'increase_percentage' => 'Increase by Percentage',
                                'decrease_percentage' => 'Decrease by Percentage',
                            ])
                            ->required()
                            ->reactive(),
                            
                        Forms\Components\TextInput::make('commission_value')
                            ->label(fn (callable $get) => match ($get('update_type')) {
                                'set_rate' => 'New Commission Rate (%)',
                                'increase_percentage' => 'Increase Percentage',
                                'decrease_percentage' => 'Decrease Percentage',
                                default => 'Value'
                            })
                            ->required()
                            ->numeric()
                            ->step(0.1)
                            ->minValue(0.1)
                            ->suffix(fn (callable $get) => $get('update_type') === 'set_rate' ? '%' : '% points')
                            ->helperText(fn (callable $get) => match ($get('update_type')) {
                                'set_rate' => 'Set all selected technicians to this commission rate',
                                'increase_percentage' => 'Add this percentage to current rates',
                                'decrease_percentage' => 'Subtract this percentage from current rates',
                                default => ''
                            }),
                            
                        Forms\Components\Textarea::make('bulk_reason')
                            ->label('Reason for Bulk Update')
                            ->placeholder('e.g., Annual rate adjustment, performance incentive')
                            ->rows(2)
                            ->required(),
                    ])
                    ->action(function (array $data, $records): void {
                        $updateType = $data['update_type'];
                        $value = $data['commission_value'];
                        $reason = $data['bulk_reason'];
                        $updatedCount = 0;
                        
                        foreach ($records as $record) {
                            $oldRate = $record->commission_rate;
                            
                            $newRate = match ($updateType) {
                                'set_rate' => $value,
                                'increase_percentage' => min(30, $record->commission_rate + $value),
                                'decrease_percentage' => max(5, $record->commission_rate - $value),
                            };
                            
                            $newRate = max(5, min(30, $newRate));
                            
                            if ($newRate !== $oldRate) {
                                $record->update(['commission_rate' => $newRate]);
                                
                                \Log::info('Bulk commission rate updated', [
                                    'technician_id' => $record->id,
                                    'technician_name' => $record->user->name,
                                    'old_commission_rate' => $oldRate,
                                    'new_commission_rate' => $newRate,
                                    'reason' => $reason,
                                    'update_type' => $updateType,
                                    'changed_by' => auth()->user()->name,
                                    'changed_at' => now(),
                                ]);
                                    
                                $updatedCount++;
                            }
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Bulk Commission Update Complete')
                            ->body("Updated commission rates for {$updatedCount} technician(s)")
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
                    
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ];
    }
}
