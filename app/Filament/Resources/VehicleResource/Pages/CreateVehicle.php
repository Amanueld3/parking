<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Filament\Resources\VehicleResource;
use App\Models\Agent;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateVehicle extends CreateRecord
{
    protected static string $resource = VehicleResource::class;


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $agent = Agent::where('user_id', auth()->id())
            ->latest()
            ->first();

        if ($agent && $agent->place_id) {
            $data['place_id'] = $agent->place_id;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        // Set check-in time and save
        $record->checkin_time = now();
        $record->save();

        // Format check-in time using app timezone
        $checkinAt = $record->checkin_time
            ? $record->checkin_time->format('Y-m-d h:i A')
            : null;

        // Build message
        $placeText = $record->place?->name ? " at {$record->place->name}" : '';
        $ownerName = trim($record->owner_name ?? '');
        $plate = (string) ($record->plate_number ?? '');

        $baseMessage = $ownerName
            ? "Hello {$ownerName}, your vehicle ({$plate}) has been checked in to parking{$placeText}."
            : "Your vehicle ({$plate}) has been checked in to parking{$placeText}.";

        $message = $checkinAt
            ? "{$baseMessage} Check-in time: {$checkinAt}."
            : $baseMessage;

        // Send SMS using trait
        $sender = new class {
            use \App\Traits\SendsSms;
            public function sendNow(string $phone, string $message): bool
            {
                return $this->sendSms($phone, $message);
            }
        };

        $sender->sendNow((string) ($record->owner_phone ?? ''), $message);

        // Filament notification
        Notification::make()
            ->title("{$plate} has been marked as checked in.")
            ->seconds(5)
            ->success()
            ->send();
    }



    protected function getRedirectUrl(): string
    {
        return url('admin/parking-desk');
    }
}
