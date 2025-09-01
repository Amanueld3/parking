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

                // License plate structured inputs (create only)
                Forms\Components\Fieldset::make('License Plate')
                    ->schema([
                        Forms\Components\Select::make('region_code')
                            ->label('Region')
                            ->options([
                                'ET' => 'ኢት / ET',
                                'AA' => 'አአ / AA',
                                'AF' => 'አፋ / AF',
                                'AM' => 'አማ / AM',
                                'BG' => 'ቤጉ / BG',
                                'DR' => 'ድሬ / DR',
                                'GM' => 'ጋም / GM',
                                'HR' => 'ሐረ / HR',
                                'OR' => 'ኦሮ / OR',
                                'SM' => 'ሶማ / SM',
                            ])
                            ->searchable()
                            ->default('AA')
                            ->required(),
                        Forms\Components\Select::make('code')
                            ->label('Code')
                            ->options(
                                collect(range(1, 10))
                                    ->mapWithKeys(fn($n) => [(string) $n => (string) $n])
                                    ->toArray()
                            )
                            ->required(),
                        Forms\Components\Select::make('series')
                            ->label('Prefix')
                            ->options([
                                '' => 'None',
                                'A' => 'A',
                                'B' => 'B',
                                'C' => 'C',
                            ])
                            ->default('')
                            ->nullable(),
                        Forms\Components\TextInput::make('number')
                            ->label('Number (5 digits)')
                            ->mask(fn() => '99999')
                            ->required()
                            ->rule('digits:5')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) {
                                $digits = preg_replace('/\D/', '', (string) $state);
                                $set('number', substr($digits, 0, 5));
                            }),
                    ])
                    ->columns(4)
                    ->visibleOn('create'),
                // Show existing plate on edit
                Forms\Components\TextInput::make('plate_number')
                    ->label('Plate Number')
                    ->disabled()
                    ->visibleOn('edit'),
                Forms\Components\Fieldset::make('Owner')
                    ->schema([
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
                        Forms\Components\TextInput::make('owner_name')
                            ->label('Full Name (optional)')
                            ->nullable()
                            ->maxLength(255),
                    ])
                    ->columns(2),
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
                        if (!$record->checkout_time) {
                            return '-';
                        }
                        $start = $record->checkin_time ? Carbon::parse($record->checkin_time) : null;
                        $end = Carbon::parse($record->checkout_time);
                        if (!$start) {
                            return '-';
                        }
                        $minutes = $start->diffInMinutes($end);
                        if ($minutes < 60) {
                            return round($minutes, 1) . ' min';
                        }
                        $hours = round($minutes / 60, 1);
                        return $hours . ' hr';
                    })
                    ->color(function ($record) {
                        if (!$record->checkout_time || !$record->checkin_time) {
                            return null;
                        }
                        return 'success';
                    })
                    ->formatStateUsing(fn($state) => "<strong>{$state}</strong>")
                    ->html(),
                Tables\Columns\TextColumn::make('place.name')
                    ->label('Place')
                    ->searchable()
                    ->sortable(),
            ])->defaultSort('checkin_time', 'desc')
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

                        $checkoutAt = $record->checkout_time
                            ? $record->checkout_time->format('Y-m-d h:i A')
                            : null;

                        $placeText = $record->place?->name ? " at {$record->place->name}" : '';
                        $ownerName = trim($record->owner_name ?? '');
                        $plate = (string) ($record->plate_number ?? '');

                        $baseMessage = $ownerName
                            ? "Hello {$ownerName}, your vehicle ({$plate}) has been checked out from parking{$placeText}."
                            : "Your vehicle ({$plate}) has been checked out from parking{$placeText}.";

                        $message = $checkoutAt
                            ? "{$baseMessage} Checkout time: {$checkoutAt}."
                            : $baseMessage;

                        $sender = new class {
                            use \App\Traits\SendsSms;
                            public function sendNow(string $phone, string $message): bool
                            {
                                return $this->sendSms($phone, $message);
                            }
                        };

                        $sender->sendNow((string) ($record->owner_phone ?? ''), $message);

                        Notification::make()
                            ->title("{$plate} has been marked as checked out.")
                            ->seconds(5)
                            ->success()
                            ->send();
                    })

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
