<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServicePricingResource\Pages;
use App\Filament\Resources\ServicePricingResource\RelationManagers;
use App\Models\ServicePricing;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\EditAction;
// DeleteAction removed as per panelist requirement
use Filament\Tables\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServicePricingResource extends Resource
{
    protected static ?string $model = ServicePricing::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Service Management';
    protected static ?string $navigationLabel = 'Service Pricing';
    protected static ?string $modelLabel = 'Service Pricing';
    protected static ?string $pluralModelLabel = 'Service Pricing';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Pricing Information')
                    ->description('Set specific pricing for service and aircon type combinations')
                    ->schema([
                        Select::make('service_id')
                            ->label('Service')
                            ->relationship('service', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                            
                        Select::make('aircon_type_id')
                            ->label('Aircon Type')
                            ->relationship('airconType', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                            
                        TextInput::make('price')
                            ->label('Price')
                            ->required()
                            ->numeric()
                            ->prefix('₱')
                            ->step(0.01)
                            ->minValue(0)
                            ->helperText('Set the specific price for this service + aircon type combination'),
                            
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Enable or disable this pricing'),
                            
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Optional notes about this pricing (e.g., includes special equipment, labor intensive, etc.)')
                    ])
                    ->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('airconType.name')
                    ->label('Aircon Type')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('price')
                    ->label('Price')
                    ->money('PHP')
                    ->sortable(),
                    
                BooleanColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),
                    
                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),
                    
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('service_id')
                    ->label('Service')
                    ->relationship('service', 'name')
                    ->searchable()
                    ->preload(),
                    
                SelectFilter::make('aircon_type_id')
                    ->label('Aircon Type')
                    ->relationship('airconType', 'name')
                    ->searchable()
                    ->preload(),
                    
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                // Delete action disabled as per panelist requirement
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Delete actions disabled as per panelist requirement
                ]),
            ])
            ->defaultSort('service.name', 'asc');
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
            'index' => Pages\ListServicePricings::route('/'),
            'create' => Pages\CreateServicePricing::route('/create'),
            'edit' => Pages\EditServicePricing::route('/{record}/edit'),
        ];
    }
}
