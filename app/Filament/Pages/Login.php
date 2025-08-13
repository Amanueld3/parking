<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Services\OtpService;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;

class Login extends BaseLogin
{
    protected static string $view = 'filament.pages.login';

    public function mount(): void
    {
        $this->form->fill([
            'otpSent' => false,
        ]);

        if (Auth::check()) {
            redirect()->intended(filament()->getUrl());
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('phone')
                    ->label('Phone Number')
                    ->required()
                    ->tel()
                    ->numeric(),

                TextInput::make('otp')
                    ->label('OTP')
                    ->numeric()
                    ->required()
                    ->hidden(fn($get) => !$get('otpSent')),
            ])
            ->statePath('data');
    }

    public function sendOtp(): void
    {
        $this->validate([
            'data.phone' => ['required', 'numeric'],
        ]);

        $phone = $this->data['phone'];
        app(OtpService::class)->sendOtp($phone);

        $this->form->fill([
            'otpSent' => true,
        ]);

        Notification::make()
            ->title('OTP sent to your phone.')
            ->success()
            ->send();
    }

    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();

        $otpService = app(OtpService::class);
        $phone = preg_replace('/[^0-9]/', '', $data['phone']);

        if (!$otpService->verifyOtp($phone, $data['otp'])) {
            Notification::make()
                ->title('Invalid OTP')
                ->danger()
                ->send();
            return null;
        }

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            Notification::make()
                ->title('User not found')
                ->danger()
                ->send();
            return null;
        }

        Auth::login($user, true);

        return app(LoginResponse::class);
    }
}
