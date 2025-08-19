<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleResource\Pages;
use App\Filament\Resources\VehicleResource\RelationManagers;
use App\Models\Vehicle;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\ActionsPosition;
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
                Tables\Columns\TextColumn::make('checkin_time')
                    ->label('Checkin Time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('checkout_time')
                    ->label('Checkout Time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->state(function ($record) {
                        $start = $record->checkin_time ? Carbon::parse($record->checkin_time) : null;
                        $end = $record->checkout_time ? Carbon::parse($record->checkout_time) : now();
                        if (!$start) {
                            return '-';
                        }
                        $minutes = $start->diffInMinutes($end);
                        if ($minutes < 60) {
                            return $minutes . ' min';
                        }
                        $hours = round($minutes / 60, 1);
                        return $hours . ' hr';
                    }),
                Tables\Columns\TextColumn::make('owner_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner_phone')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('place.name')
                    ->label('Place')
                    ->searchable()
                    ->sortable(),
            ])->defaultSort('checkout_time', 'asc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('checkout')
                    ->label('Checkout')
                    ->icon('heroicon-o-check-circle')
                    ->visible(function ($record) {
                        if ($record->checkout_time === null) {
                            return true;
                        } else {
                            return false;
                        }
                    })
                    ->color('danger')
                    ->action(function ($record) {
                        $record->checkout_time = now();
                        $record->save();

                        // Build checkout SMS message (no logging)
                        $tz = config('app.timezone', 'UTC');
                        $checkoutAt = $record->checkout_time;
                        $timeText = optional($checkoutAt)->setTimezone($tz)?->format('Y-m-d H:i');
                        $placeName = $record->place?->name;
                        $placeText = $placeName ? " at {$placeName}" : '';
                        $ownerName = trim((string) ($record->owner_name ?? ''));
                        $plate = (string) ($record->plate_number ?? '');
                        $base = $ownerName !== ''
                            ? "Hello {$ownerName}, your vehicle ({$plate}) has been checked out from parking{$placeText}."
                            : "Your vehicle ({$plate}) has been checked out from parking{$placeText}.";
                        $message = $timeText ? ($base . " Checkout time: {$timeText}.") : $base;

                        // Send via SendsSms trait using anonymous helper (static context safe)
                        $sender = new class {
                            use \App\Traits\SendsSms;
                            public function sendNow(string $phone, string $message): bool
                            {
                                return $this->sendSms($phone, $message);
                            }
                        };
                        $sender->sendNow((string) ($record->owner_phone ?? ''), $message);

                        Notification::make()
                            ->title("{$record->plate_number} has been marked as checked out.")
                            ->seconds(5)
                            ->success()
                            ->send();
                    }),
                // Tables\Actions\ActionGroup::make([
                //     Tables\Actions\EditAction::make(),
                //     Tables\Actions\ViewAction::make(),
                //     Tables\Actions\DeleteAction::make(),
                // ]),
            ], position: ActionsPosition::BeforeCells)
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
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
