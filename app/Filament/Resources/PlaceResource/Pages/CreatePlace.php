<?php

namespace App\Filament\Resources\PlaceResource\Pages;

use App\Filament\Resources\PlaceResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePlace extends CreateRecord
{
    protected static string $resource = PlaceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = User::create([
            'name' => $data['owner_name'],
            'phone' => $data['owner_phone'],
            'email' => $data['owner_email'] ?? null,
            'password' => bcrypt($data['owner_password']),
        ]);

        $user->assignRole('owner');

        $data['owner_id'] = $user->id;

        unset(
            $data['owner_name'],
            $data['owner_phone'],
            $data['owner_email'],
            $data['owner_password'],
            $data['owner_password_confirmation']
        );

        return $data;
    }
}
