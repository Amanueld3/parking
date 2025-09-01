<?php

namespace App\Filament\Resources\PlaceResource\Pages;

use App\Filament\Resources\PlaceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlace extends EditRecord
{
    protected static string $resource = PlaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->owner) {
            $data['owner_name'] = $this->record->owner->name;
            $data['owner_phone'] = $this->record->owner->phone;
            $data['owner_email'] = $this->record->owner->email;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->owner) {
            $this->record->owner->update([
                'name' => $data['owner_name'],
                'phone' => $data['owner_phone'],
                'email' => $data['owner_email'] ?? null,
                'password' => !empty($data['owner_password'])
                    ? bcrypt($data['owner_password'])
                    : $this->record->owner->password,
            ]);
        }

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
