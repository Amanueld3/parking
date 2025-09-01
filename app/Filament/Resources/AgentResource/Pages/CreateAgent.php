<?php

namespace App\Filament\Resources\AgentResource\Pages;

use App\Filament\Resources\AgentResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateAgent extends CreateRecord
{
    protected static string $resource = AgentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['user']['phone'])) {
            $phone = preg_replace('/\D/', '', (string) $data['user']['phone']);
            if ($phone !== '' && ($phone[0] ?? null) !== '9') {
                $phone = '9' . substr($phone, 0, 8);
            }
            if (strlen($phone) > 9) {
                $phone = substr($phone, -9);
            }
            $data['user']['phone'] = $phone;
        }

        $user = User::create([
            'name' => $data['user']['name'],
            'email' => $data['user']['email'] ?? null,
            'phone' => $data['user']['phone'] ?? null,
            'password' => Hash::make('password1234'),
        ]);

        if (!$user->hasRole('agent')) {
            $user->assignRole('agent');
        }

        $data['user_id'] = $user->id;
        $data['created_by'] = auth()->id();
        $data['status'] = $data['status'] ?? 1;
        unset($data['user']);

        return $data;
    }
}
