<?php

namespace App\Filament\Resources\PlaceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SlotsRelationManager extends RelationManager
{
    protected static string $relationship = 'slots';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->required(),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('slot_number', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('slot_number')->sortable()->searchable(),
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
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
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
}
