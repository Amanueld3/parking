<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleResource\Pages;
use App\Filament\Resources\VehicleResource\RelationManagers;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('place_id')
                    ->default(function () {
                        $agent = \App\Models\Agent::where('user_id', auth()->id())
                            ->latest()
                            ->first();
                        return $agent?->place_id;
                    })
                    ->dehydrated() // ensure it's saved
                    ->required(),

                Forms\Components\TextInput::make('plate_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('owner_name')
                    ->label('Full Name')
                    ->nullable()
                    ->maxLength(255),
                Forms\Components\TextInput::make('owner_phone')
                    ->label('Phone')
                    ->required()
                    ->prefix('+251')
                    ->placeholder('9XXXXXXXX')
                    ->mask(fn() => '999999999')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set) {
                        $cleaned = preg_replace('/\D/', '', $state);

                        if ($cleaned !== '' && $cleaned[0] !== '9') {
                            $cleaned = '9' . substr($cleaned, 0, 8);
                        }
                        if (strlen($cleaned) > 9) {
                            $cleaned = substr($cleaned, -9);
                        }

                        $set('owner_phone', $cleaned);
                    })
                    ->dehydrateStateUsing(function ($state) {
                        return $state;
                    }),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('plate_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner_phone')
                    ->searchable()
                    ->sortable(),
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
            'index' => Pages\ListVehicles::route('/'),
            // Removed the dedicated create page to use modal creation on index instead
            // 'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
