<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlaceResource\Pages;
use App\Filament\Resources\PlaceResource\RelationManagers\AgentsRelationManager;
use App\Filament\Resources\PlaceResource\RelationManagers\SlotsRelationManager;
use App\Models\Place;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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
                            ->label('Sub City')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter sub city'),

                        Forms\Components\TextInput::make('address.district')
                            ->label('District')
                            ->required()
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

                Forms\Components\Fieldset::make('Owner')
                    ->schema([
                        Forms\Components\TextInput::make('owner_name')
                            ->label('Name')
                            ->required(),

                        Forms\Components\TextInput::make('owner_phone')
                            ->label('Phone')
                            ->required()
                            ->prefix('+251')
                            ->placeholder('9XXXXXXXX')
                            ->mask(fn() => '999999999')
                            ->live(onBlur: true)
                            ->rules([
                                'regex:/^9\d{8}$/',
                                function ($record) {
                                    return Rule::unique('users', 'phone')
                                        ->ignore($record?->owner?->id);
                                }
                            ])
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Remove any non-numeric characters
                                $cleaned = preg_replace('/\D/', '', $state);

                                // Ensure starts with 9
                                if ($cleaned !== '' && $cleaned[0] !== '9') {
                                    $cleaned = '9' . substr($cleaned, 0, 8);
                                }

                                // If length > 9, remove from the front
                                if (strlen($cleaned) > 9) {
                                    $cleaned = substr($cleaned, -9);
                                }

                                $set('owner_phone', $cleaned);
                            })
                            ->dehydrateStateUsing(function ($state) {
                                return $state; // Keep as is, since prefix is only visual
                            }),

                        Forms\Components\TextInput::make('owner_email')
                            ->label('Email')
                            ->email()
                            ->rules([
                                function ($record) {
                                    return Rule::unique('users', 'email')
                                        ->whereNot('id', $record?->owner?->id);
                                }
                            ])
                            ->required(),

                        Forms\Components\Hidden::make('owner_password')
                            ->default('password1234')
                            ->dehydrateStateUsing(fn($state) => Hash::make($state)),
                    ])
                    ->columns(3),

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
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Owner Name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('owner.email')
                    ->label('Owner Email')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('owner.phone')
                    ->label('Owner Phone')
                    ->formatStateUsing(fn($state) => $state ? '+251' . $state : '-')
                    ->toggleable(isToggledHiddenByDefault: true),

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
            AgentsRelationManager::class,
            SlotsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlaces::route('/'),
            'create' => Pages\CreatePlace::route('/create'),
            'edit' => Pages\EditPlace::route('/{record}/edit'),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Place Details')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Place Name'),
                        TextEntry::make('capacity')
                            ->label('Capacity'),
                        TextEntry::make('address.city')
                            ->label('City'),
                        TextEntry::make('address.subcity')
                            ->label('Sub City'),
                        TextEntry::make('address.district')
                            ->label('District'),
                        TextEntry::make('location.lat')
                            ->label('Latitude'),
                        TextEntry::make('location.long')
                            ->label('Longitude'),
                    ])
                    ->columns(2),

                Section::make('Owner Details')
                    ->schema([
                        TextEntry::make('owner.name')
                            ->label('Owner Name'),
                        TextEntry::make('owner.phone')
                            ->label('Phone')
                            ->formatStateUsing(fn($state) => $state ? '+251' . $state : '-'),
                        TextEntry::make('owner.email')
                            ->label('Email'),
                    ])
                    ->columns(2),
            ]);
    }
}
