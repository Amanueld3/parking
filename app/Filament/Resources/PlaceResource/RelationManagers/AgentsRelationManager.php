<?php

namespace App\Filament\Resources\PlaceResource\RelationManagers;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class AgentsRelationManager extends RelationManager
{
    protected static string $relationship = 'agents';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Fieldset::make('Agent Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required(),

                        Forms\Components\TextInput::make('phone')
                            ->label('Phone')
                            ->required()
                            ->prefix('+251')
                            ->placeholder('9XXXXXXXX')
                            ->mask('999999999')
                            ->unique(table: 'users', column: 'phone', ignoreRecord: true)
                            ->live(onBlur: true)
                            ->dehydrateStateUsing(function ($state) {
                                return preg_match('/^9\d{8}$/', $state) ? substr($state, 1) : $state;
                            }),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->unique(table: 'users', column: 'email', ignoreRecord: true)
                            ->required(),
                        Forms\Components\Hidden::make('password')
                            ->default('password1234')
                            ->dehydrateStateUsing(fn($state) => Hash::make($state)),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user.name')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name'),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email'),
                Tables\Columns\TextColumn::make('user.phone')
                    ->label('Phone'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(int $state): string => match ($state) {
                        0 => 'danger',
                        1 => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => $state === 1 ? 'Active' : 'Inactive'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->using(function (array $data, string $model): Model {
                        $user = User::create([
                            'name' => $data['name'],
                            'email' => $data['email'],
                            'phone' => $data['phone'],
                            'password' => $data['password'],
                        ]);

                        return $model::create([
                            'place_id' => $this->getOwnerRecord()->id,
                            'user_id' => $user->id,
                            'created_by' => auth()->id(),
                        ]);
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->form([])
                    ->modalContent(function (Model $record) {
                        return view('filament.agents.view', [
                            'record' => $record,
                            'user' => $record->user,
                        ]);
                    })
                    ->modalHeading('Agent Details')
                    ->modalWidth('xl'),

                Tables\Actions\EditAction::make()
                    ->form([
                        Forms\Components\Section::make('Agent Details')
                            ->schema([
                                Forms\Components\TextInput::make('user.name')
                                    ->label('Name')
                                    ->required(),
                                Forms\Components\TextInput::make('user.email')
                                    ->label('Email')
                                    ->email()
                                    ->required(),
                                Forms\Components\TextInput::make('user.phone')
                                    ->label('Phone')
                                    ->required(),
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        0 => 'Inactive',
                                        1 => 'Active',
                                    ])
                                    ->required(),
                            ])
                            ->columns(2),
                    ])
                    ->mutateRecordDataUsing(function (array $data): array {
                        // Load user data when opening edit form
                        $agent = $this->getOwnerRecord()->agents()->find($data['id']);
                        return [
                            ...$data,
                            'user' => [
                                'name' => $agent->user->name,
                                'email' => $agent->user->email,
                                'phone' => $agent->user->phone,
                            ],
                            'status' => $agent->status,
                        ];
                    })
                    ->using(function (Model $record, array $data): Model {
                        $record->user->update([
                            'name' => $data['user']['name'],
                            'email' => $data['user']['email'],
                            'phone' => $data['user']['phone'],
                        ]);

                        $record->update(['status' => $data['status']]);

                        return $record;
                    }),

                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
