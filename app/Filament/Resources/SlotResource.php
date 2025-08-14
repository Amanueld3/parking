<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SlotResource\Pages;
use App\Filament\Resources\SlotResource\RelationManagers;
use App\Models\Slot;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SlotResource extends Resource
{
    protected static ?string $model = Slot::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('slot_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slot_number')
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        table: 'slots',
                        column: 'slot_number',
                        ignoreRecord: true,
                        modifyRuleUsing: fn(\Illuminate\Validation\Rules\Unique $rule) => $rule->where('place_id', $this->getOwnerRecord()->getKey()),
                    ),

                Forms\Components\Select::make('status')
                    ->options([
                        'available' => 'Available',
                        'occupied' => 'Occupied',
                        'maintenance' => 'Maintenance',
                        'unavailable' => 'Unavailable',
                    ])
                    ->default('available')
                    ->required()
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slot_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('place.name')
                    ->label('Place')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->sortable()
                    ->formatStateUsing(fn($state) => ucfirst($state))
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'available' => 'success',
                        'occupied' => 'warning',
                        'maintenance' => 'gray',
                        'unavailable' => 'danger',
                        default => null,
                    }),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('place_id')
                    ->relationship('place', 'name', fn(Builder $query) => $query->where('owner_id', auth()->id()))
                    ->label('Place')
                    ->query(function (Builder $query) {
                        return $query->whereHas('place', function ($q) {
                            $q->where('owner_id', auth()->id());
                        });
                    }),
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

    // Only show slots whose related place is owned by the authenticated user
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('place', fn(Builder $query) => $query->where('owner_id', auth()->id()));
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
            'index' => Pages\ListSlots::route('/'),
            'create' => Pages\CreateSlot::route('/create'),
            'edit' => Pages\EditSlot::route('/{record}/edit'),
        ];
    }
}
