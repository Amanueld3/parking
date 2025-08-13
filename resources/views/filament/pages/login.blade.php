<x-filament-panels::page.simple>
    {{ $this->form }}

    <div class="flex mt-4 space-x-2">
        @if (!$this->data['otpSent'] ?? false)
            <x-filament::button wire:click="sendOtp" wire:loading.attr="disabled" wire:target="sendOtp">
                Send OTP
            </x-filament::button>
        @else
            <x-filament::button wire:click="authenticate" wire:loading.attr="disabled" wire:target="authenticate">
                Login
            </x-filament::button>
        @endif
    </div>
</x-filament-panels::page.simple>
