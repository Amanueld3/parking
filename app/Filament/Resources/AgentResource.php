<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgentResource\Pages;
use App\Models\Agent;
use App\Models\Place;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class AgentResource extends Resource
{
    protected static ?string $model = Agent::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $modelLabel = 'Agent';
    protected static ?string $navigationLabel = 'Agents';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('place_id')
                    ->label('Place')
                    ->relationship('place', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Section::make('Agent User')
                    ->schema([
                        Forms\Components\TextInput::make('user.name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('user.email')
                            ->label('Email')
                            ->email()
                            ->nullable()
                            ->rules([
                                function () {
                                    return Rule::unique('users', 'email');
                                }
                            ]),

                        Forms\Components\TextInput::make('user.phone')
                            ->label('Phone')
                            ->required()
                            ->prefix('+251')
                            ->placeholder('9XXXXXXXX')
                            ->mask(fn() => '999999999')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) {
                                $cleaned = preg_replace('/\D/', '', (string) $state);

                                if ($cleaned !== '' && ($cleaned[0] ?? null) !== '9') {
                                    $cleaned = '9' . substr($cleaned, 0, 8);
                                }
                                if (strlen($cleaned) > 9) {
                                    $cleaned = substr($cleaned, -9);
                                }

                                $set('user.phone', $cleaned);
                            })
                            ->dehydrateStateUsing(fn($state) => $state)
                            ->rules([
                                'regex:/^9\d{8}$/',
                                function () {
                                    return Rule::unique('users', 'phone');
                                }
                            ]),
                    ])
                    ->columns(3),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        0 => 'Inactive',
                        1 => 'Active',
                    ])
                    ->default(1)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.phone')
                    ->label('Phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('place.name')
                    ->label('Place')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(int $state): string => match ($state) {
                        0 => 'danger',
                        1 => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => $state === 1 ? 'Active' : 'Inactive'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgents::route('/'),
            'create' => Pages\CreateAgent::route('/create'),
            'edit' => Pages\EditAgent::route('/{record}/edit'),
        ];
    }
}
