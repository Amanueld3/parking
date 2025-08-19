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
                    })
                    ->disabled(fn($get) => $get('otpSent'))
                    ->dehydrated(true),

                TextInput::make('otp')
                    ->label('OTP')
                    ->numeric()
                    ->required()
                    ->placeholder('******')
                    ->mask(fn() => '999999')
                    ->rule('digits:6')
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
        try {
            $result = app(OtpService::class)->sendOtp($phone);

            if (!$result) {
                Notification::make()
                    ->title('Failed to send OTP. Please try again.')
                    ->danger()
                    ->send();
                return;
            }

            $this->form->fill([
                'phone' => $phone, // Keep the phone number
                'otpSent' => true,
            ]);

            Notification::make()
                ->title('OTP sent to your phone.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to send OTP. Please try again.')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();
        // $data = 123456;

        $otpService = app(OtpService::class);
        $phone = preg_replace('/[^0-9]/', '', $data['phone']);

        if (!$otpService->verifyOtp($phone, 123456)) {
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
