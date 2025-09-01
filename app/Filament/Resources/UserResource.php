<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Users';

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
                        'regex:/^9\\d{8}$/',
                        function ($record) {
                            return Rule::unique('users', 'phone')->ignore($record?->id);
                        },
                    ])
                    ->afterStateUpdated(function ($state, callable $set) {
                        $cleaned = preg_replace('/\\D/', '', (string) $state);
                        if ($cleaned !== '' && ($cleaned[0] ?? null) !== '9') {
                            $cleaned = '9' . substr($cleaned, 0, 8);
                        }
                        if (strlen($cleaned) > 9) {
                            $cleaned = substr($cleaned, -9);
                        }
                        $set('phone', $cleaned);
                    })
                    ->dehydrateStateUsing(fn($state) => $state),

                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->nullable()
                    ->rules([
                        function ($record) {
                            return Rule::unique('users', 'email')->ignore($record?->id);
                        },
                    ]),

                Forms\Components\Select::make('roles')
                    ->label('Roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('phone')->prefix('+251')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('roles')
                    ->label('Roles')
                    ->state(function ($record) {
                        return $record->roles?->pluck('name')->all() ?? [];
                    })
                    ->badge()
                    ->separator(', '),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
