<?php

namespace App\Filament\Resources\AgentResource\Pages;

use App\Filament\Resources\AgentResource;
use Filament\Resources\Pages\EditRecord;

class EditAgent extends EditRecord
{
    protected static string $resource = AgentResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Pre-fill nested user fields
        $record = $this->getRecord();
        $data['user'] = [
            'name' => $record->user?->name,
            'email' => $record->user?->email,
            'phone' => $record->user?->phone,
        ];
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Update underlying user
        $record = $this->getRecord();
        if (isset($data['user'])) {
            $phone = preg_replace('/\D/', '', (string) ($data['user']['phone'] ?? ''));
            if ($phone !== '' && ($phone[0] ?? null) !== '9') {
                $phone = '9' . substr($phone, 0, 8);
            }
            if (strlen($phone) > 9) {
                $phone = substr($phone, -9);
            }

            $record->user->update([
                'name' => $data['user']['name'] ?? $record->user->name,
                'email' => $data['user']['email'] ?? $record->user->email,
                'phone' => $phone,
            ]);

            unset($data['user']);
        }

        return $data;
    }
}
