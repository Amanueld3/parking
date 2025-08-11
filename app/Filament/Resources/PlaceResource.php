<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlaceResource\Pages;
use App\Filament\Resources\PlaceResource\RelationManagers\SlotsRelationManager;
use App\Models\Place;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlaceResource extends Resource
{
    protected static ?string $model = Place::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $modelLabel = 'Place';

    protected static ?string $navigationGroup = 'Setups';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Place Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Enter place name'),

                Forms\Components\TextInput::make('capacity')
                    ->numeric()
                    ->nullable()
                    ->label('Capacity')
                    ->placeholder('e.g. 100'),

                Forms\Components\Fieldset::make('Address')
                    ->schema([
                        Forms\Components\TextInput::make('address.city')
                            ->label('City')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter city'),

                        Forms\Components\TextInput::make('address.subcity')
                            ->label('Subcity')
                            ->nullable()
                            ->maxLength(255)
                            ->placeholder('Enter subcity'),

                        Forms\Components\TextInput::make('address.district')
                            ->label('District')
                            ->nullable()
                            ->maxLength(255)
                            ->placeholder('Enter district'),
                    ])
                    ->columns(3),

                Forms\Components\Fieldset::make('Location')
                    ->schema([
                        Forms\Components\TextInput::make('location.lat')
                            ->label('Latitude')
                            ->numeric()
                            ->nullable()
                            ->placeholder('e.g. 9.005401'),

                        Forms\Components\TextInput::make('location.long')
                            ->label('Longitude')
                            ->numeric()
                            ->nullable()
                            ->placeholder('e.g. 38.763611'),
                    ]),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('capacity')
                    ->sortable(),

                Tables\Columns\TextColumn::make('address')
                    ->formatStateUsing(fn($state) => $state ? json_encode($state) : '-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('location')
                    ->formatStateUsing(fn($state) => $state ? json_encode($state) : '-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SlotsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlaces::route('/'),
            'view' => Pages\ViewPlace::route('/{record}'),
            'create' => Pages\CreatePlace::route('/create'),
            'edit' => Pages\EditPlace::route('/{record}/edit'),
        ];
    }
}
