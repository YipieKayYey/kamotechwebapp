<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AirconTypeResource\Pages;
use App\Models\AirconType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AirconTypeResource extends Resource
{
    protected static ?string $model = AirconType::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationGroup = 'Service Management';
    protected static ?string $navigationLabel = 'Aircon Types';
    protected static ?string $modelLabel = 'Aircon Type';
    protected static ?string $pluralModelLabel = 'Aircon Types';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Aircon Type Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Type Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Window Type, Split Type'),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->placeholder('Brief description of this aircon type...'),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive types will not be available for new bookings'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Type Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('bookings_count')
                    ->label('Total Bookings')
                    ->counts('bookings')
                    ->sortable()
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All types')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No aircon types found')
            ->emptyStateDescription('Create your first aircon type to start managing AC service types.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAirconTypes::route('/'),
            'create' => Pages\CreateAirconType::route('/create'),
            'view' => Pages\ViewAirconType::route('/{record}'),
            'edit' => Pages\EditAirconType::route('/{record}/edit'),
        ];
    }
}