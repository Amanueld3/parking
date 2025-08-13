<?php

namespace App\Filament\Resources\PlaceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AgentsRelationManager extends RelationManager
{
    protected static string $relationship = 'agents';

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Fieldset::make('Agent Details')
                    ->schema([
                        Forms\Components\TextInput::make('agent_name')
                            ->label('Name')
                            ->required(),

                        Forms\Components\TextInput::make('agent_phone')
                            ->label('Phone')
                            ->required()
                            ->prefix('+251')
                            ->placeholder('9XXXXXXXX')
                            ->mask(fn() => '999999999')
                            ->unique(table: 'users', column: 'phone')
                            ->live(onBlur: true)
                            ->dehydrateStateUsing(function ($state) {
                                if (preg_match('/^9\d{8}$/', $state)) {
                                    return substr($state, 1);
                                }
                                return $state;
                            }),


                        Forms\Components\TextInput::make('agent_email')
                            ->label('Email')
                            ->email()
                            ->unique(table: 'users', column: 'email')
                            ->required(),

                        Forms\Components\TextInput::make('agent_password')
                            ->label('Password')
                            ->password()
                            ->required(fn($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                            ->minLength(8)
                            ->revealable(),

                        Forms\Components\TextInput::make('agent_password_confirmation')
                            ->label('Confirm Password')
                            ->password()
                            ->required(fn($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                            ->same('agent_password')
                            ->revealable(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('agent')
            ->columns([])
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
