<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Models\Place;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $navigationLabel = 'Transactions';
    // protected static ?string $modelLabel = 'Transaction';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('vehicle.plate_number')
                    ->label('Plate')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn($state) => strtoupper((string) $state)),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->sortable()
                    ->formatStateUsing(fn($state) => 'ETB ' . number_format((float) $state, 2)),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'initialized' => 'primary',
                        'pending' => 'warning',
                        'success' => 'success',
                        'failed', 'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('vehicle.place.name')
                    ->label('Place')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('vehicle.creator.name')
                    ->label('Agent')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('tx_ref')
                    ->label('Ref')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('place_id')
                    ->label('Place')
                    ->options(function () {
                        $query = Place::query();
                        $user = User::find(auth()->id());
                        if ($user && ! $user->hasRole('super_admin')) {
                            $query->where('owner_id', $user->id);
                        }
                        return $query->orderBy('name')->pluck('name', 'id')->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['value'])) {
                            $query->whereHas('vehicle', function ($q) use ($data) {
                                $q->where('place_id', $data['value']);
                            });
                        }
                    }),
                SelectFilter::make('agent_id')
                    ->label('Agent')
                    ->options(function () {
                        $auth = auth()->user();
                        if ($auth && $auth->hasRole('super_admin')) {
                            $userIds = \App\Models\Agent::query()->select('user_id')->distinct()->pluck('user_id');
                            return User::whereIn('id', $userIds)->orderBy('name')->pluck('name', 'id')->toArray();
                        }
                        // Owner: only agents under their places
                        $placeIds = Place::query()->where('owner_id', $auth?->id)->pluck('id');
                        $agentUserIds = \App\Models\Agent::query()
                            ->whereIn('place_id', $placeIds)
                            ->select('user_id')
                            ->distinct()
                            ->pluck('user_id');
                        return User::whereIn('id', $agentUserIds)->orderBy('name')->pluck('name', 'id')->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['value'])) {
                            $query->whereHas('vehicle', function ($q) use ($data) {
                                $q->where('created_by', $data['value']);
                            });
                        }
                    }),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'initialized' => 'initialized',
                        'pending' => 'pending',
                        'success' => 'success',
                        'failed' => 'failed',
                        'cancelled' => 'cancelled',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = User::find(auth()->id());
        $base = parent::getEloquentQuery()->with(['vehicle.place', 'vehicle.creator']);

        if ($user && $user->hasRole('super_admin')) {
            return $base;
        }

        if ($user && $user->hasRole('agent')) {
            return $base->whereHas('vehicle', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        }

        // Owner: show payments for places they own
        return $base->whereHas('vehicle.place', function ($q) use ($user) {
            $q->where('owner_id', $user?->id);
        });
    }

    public static function shouldRegisterNavigation(): bool
    {
        // $user = auth()->user();
        // if (! $user) return false;
        // return $user->hasRole('super_admin') || $user->hasRole('owner');

        return true;
    }
}
