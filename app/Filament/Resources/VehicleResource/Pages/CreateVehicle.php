<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Filament\Resources\VehicleResource;
use App\Models\Agent;
use Filament\Actions;
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
}
