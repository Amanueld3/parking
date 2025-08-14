<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Filament\Resources\VehicleResource;
use App\Traits\SendsSms;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Config;

class ListVehicles extends ListRecords
{
    use SendsSms;

    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Vehicle')
                ->modalHeading('Create Vehicle')
                ->modalSubmitActionLabel('Add Vehicle')
                ->modalWidth('sm')
                ->modalCancelAction(false)
                ->after(function ($record) {
                    try {
                        if (! $record) {
                            return;
                        }

                        $ownerPhone = (string) ($record->owner_phone ?? '');
                        $plate = (string) ($record->plate_number ?? '');
                        $ownerName = trim((string) ($record->owner_name ?? ''));

                        $tz = (string) Config::get('app.timezone', 'UTC');
                        $checkinAt = $record->checkin_time ?? $record->created_at;
                        $timeText = optional($checkinAt)->setTimezone($tz)?->format('Y-m-d H:i');

                        $placeName = $record->place?->name;
                        $placeText = $placeName ? " at {$placeName}" : '';
                        $base = $ownerName !== ''
                            ? "Hello {$ownerName}, your vehicle ({$plate}) has been registered for parking{$placeText}."
                            : "Your vehicle ({$plate}) has been registered for parking{$placeText}.";
                        $message = $timeText ? ($base . " Checkin time: {$timeText}.") : $base;

                        $this->sendSms($ownerPhone, $message);
                    } catch (\Throwable $e) {
                        return; // silent
                    }
                }),
        ];
    }
}
