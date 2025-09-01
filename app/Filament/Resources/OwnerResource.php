<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OwnerResource\Pages;
use App\Filament\Resources\OwnerResource\RelationManagers;
use App\Filament\Resources\OwnerResource\RelationManagers\PlacesRelationManager;
use App\Models\Owner;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class OwnerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Owners';

    public static function getPluralModelLabel(): string
    {
        return 'Owners';
    }

    public static function getModelLabel(): string
    {
        return 'Owner';
    }

    // Restrict listing to users with the 'owner' role
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->role('owner');
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required(),

                Forms\Components\TextInput::make('phone')
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
                                ->ignore($record?->id);
                        }
                    ])
                    ->afterStateUpdated(function ($state, callable $set) {
                        $cleaned = preg_replace('/\D/', '', $state);

                        if ($cleaned !== '' && $cleaned[0] !== '9') {
                            $cleaned = '9' . substr($cleaned, 0, 8);
                        }
                        if (strlen($cleaned) > 9) {
                            $cleaned = substr($cleaned, -9);
                        }

                        $set('phone', $cleaned);
                    })
                    ->dehydrateStateUsing(function ($state) {
                        return $state;
                    }),

                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->nullable()
                    ->rules([
                        function ($record) {
                            return Rule::unique('users', 'email')
                                ->whereNot('id', $record?->id);
                        }
                    ])
                    ->required(false),

                Forms\Components\Hidden::make('password')
                    ->default('password1234')
                    ->dehydrateStateUsing(fn($state) => Hash::make($state)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Owner Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->sortable()
                    ->prefix('+251'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
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
            PlacesRelationManager::class,
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOwners::route('/'),
            'create' => Pages\CreateOwner::route('/create'),
            'edit' => Pages\EditOwner::route('/{record}/edit'),
        ];
    }
}
