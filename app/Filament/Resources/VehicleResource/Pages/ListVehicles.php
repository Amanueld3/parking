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
                        $message = \App\Services\SmsTemplateService::formatCheckin($record);
                        $this->sendSms($ownerPhone, $message);
                    } catch (\Throwable $e) {
                        return; // silent
                    }
                }),
        ];
    }
}
