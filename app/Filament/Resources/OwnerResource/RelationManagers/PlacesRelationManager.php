<?php

namespace App\Filament\Resources\OwnerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Filament\Forms\Get;

class PlacesRelationManager extends RelationManager
{
    protected static string $relationship = 'places';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Place Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Enter place name'),

                Forms\Components\Fieldset::make('Address')
                    ->schema([
                        Forms\Components\Select::make('address.city')
                            ->label('City')
                            ->required()
                            ->options([
                                'Addis Ababa' => 'Addis Ababa',
                                'Adama' => 'Adama',
                                // 'Bahir Dar' => 'Bahir Dar',
                                // 'Dire Dawa' => 'Dire Dawa',
                                // 'Hawassa' => 'Hawassa',
                                // 'Mekelle' => 'Mekelle',
                                // 'Gondar' => 'Gondar',
                                // 'Jimma' => 'Jimma',
                                // 'Shashamane' => 'Shashamane',
                                // 'Dessie' => 'Dessie',
                                // 'Arba Minch' => 'Arba Minch',
                                // 'Harar' => 'Harar',
                                // 'Sodo' => 'Sodo',
                                // 'Debre Birhan' => 'Debre Birhan',
                                // 'Asella' => 'Asella',
                                // 'Dilla' => 'Dilla',
                                // 'Debre Markos' => 'Debre Markos',
                                // 'Hosaena' => 'Hosaena',
                                // 'Nekemte' => 'Nekemte',
                                // 'Wolaita Sodo' => 'Wolaita Sodo',
                            ])
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('address.subcity', null);
                            })
                            ->placeholder('Select city'),

                        Forms\Components\Select::make('address.subcity')
                            ->label('Sub City')
                            ->required()
                            ->options(function (callable $get) {
                                $city = $get('address.city');

                                $map = [
                                    'Addis Ababa' => [
                                        'Addis Ketema' => 'Addis Ketema',
                                        'Akaki Kality' => 'Akaki Kality',
                                        'Arada' => 'Arada',
                                        'Bole' => 'Bole',
                                        'Gullele' => 'Gullele',
                                        'Kirkos' => 'Kirkos',
                                        'Kolfe Keranio' => 'Kolfe Keranio',
                                        'Lemi Kura' => 'Lemi Kura',
                                        'Lideta' => 'Lideta',
                                        'Nifas Silk-Lafto' => 'Nifas Silk-Lafto',
                                        'Yeka' => 'Yeka',
                                    ],
                                    'Adama' => [
                                        '01' => '01',
                                        '02' => '02',
                                        '03' => '03',
                                        '04' => '04',
                                        '05' => '05',
                                        '06' => '06',
                                        '07' => '07',
                                        '08' => '08',
                                    ],
                                ];

                                return $map[$city] ?? [];
                            })
                            ->searchable()
                            ->disabled(fn(callable $get) => blank($get('address.city')))
                            ->placeholder(fn(callable $get) => $get('address.city') ? 'Select sub city' : 'Select a city first'),

                        Forms\Components\TextInput::make('address.district')
                            ->label('District')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter district'),
                    ])
                    ->columns(3),

                Forms\Components\Fieldset::make('Location')
                    ->schema([
                        Forms\Components\Radio::make('location_mode')
                            ->label('Set location by')
                            ->options([
                                'manual' => 'Enter coordinates',
                                // 'map' => 'Pick on map',
                            ])
                            ->default('manual')
                            ->inline()
                            ->dehydrated(false)
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('location.lat')
                            ->label('Latitude')
                            ->numeric()
                            ->nullable()
                            ->placeholder('e.g. 9.005401')
                            ->visible(fn(Get $get) => ($get('location_mode') ?? 'manual') === 'manual'),

                        Forms\Components\TextInput::make('location.long')
                            ->label('Longitude')
                            ->numeric()
                            ->nullable()
                            ->placeholder('e.g. 38.763611')
                            ->visible(fn(Get $get) => ($get('location_mode') ?? 'manual') === 'manual'),

                        Forms\Components\View::make('filament.components.map-picker')
                            ->visible(fn(Get $get) => $get('location_mode') === 'map')
                            ->columnSpanFull()
                            ->viewData(fn(Get $get) => [
                                'lat' => $get('location.lat') ?: 9.005401,
                                'lng' => $get('location.long') ?: 38.763611,
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Place')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Place Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slots_count')
                    ->label('Slots')
                    ->counts('slots')
                    ->sortable(),
                Tables\Columns\TextColumn::make('address.city')
                    ->label('City')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('address.subcity')
                    ->label('Sub City')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('address.district')
                    ->label('District')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Created At'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
