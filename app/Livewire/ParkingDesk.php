<?php

namespace App\Livewire;

use App\Models\Agent;
use App\Models\Vehicle;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Livewire\Component;

class ParkingDesk extends Component
{
    public bool $showNew = false;
    public bool $showCheckout = false;

    // View control flags
    public bool $hideButtons = false;       // Hide the top action buttons (used on Checkout page)
    public bool $inlineCheckout = false;    // Render checkout as inline content instead of a modal

    public ?int $place_id = null;

    public string $plate_number = '';
    public ?string $owner_name = null;
    public string $owner_phone = '';

    public string $query = '';

    protected function rules(): array
    {
        return [
            'plate_number' => ['required', 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'owner_phone' => ['required', 'regex:/^\d{9}$/'],
        ];
    }

    public function mount(bool $showCheckout = false, bool $inlineCheckout = false, bool $hideButtons = false): void
    {
        $agent = Agent::where('user_id', Auth::id())->latest()->first();
        $this->place_id = $agent?->place_id !== null ? (int) $agent->place_id : null;
        // Initialize view flags
        $this->inlineCheckout = $inlineCheckout;
        $this->hideButtons = $hideButtons;

        // Only open modal when not using inline checkout
        if ($showCheckout && ! $this->inlineCheckout) {
            $this->showCheckout = true;
        }
    }

    public function openNewVehicle(): void
    {
        $this->showNew = true;
    }

    public function closeNewVehicle(): void
    {
        $this->showNew = false;
    }

    public function openCheckout(): void
    {
        $this->showCheckout = true;
    }

    public function closeCheckout(): void
    {
        $this->showCheckout = false;
    }

    public function updatedOwnerPhone($value): void
    {
        $cleaned = preg_replace('/\D/', '', (string) $value);
        if ($cleaned !== '' && $cleaned[0] !== '9') {
            $cleaned = '9' . substr($cleaned, 0, 8);
        }
        if (strlen($cleaned) > 9) {
            $cleaned = substr($cleaned, -9);
        }
        $this->owner_phone = $cleaned;
    }

    public function saveNewVehicle(): void
    {
        $this->validate();

        $vehicle = Vehicle::create([
            'place_id' => $this->place_id,
            'plate_number' => $this->plate_number,
            'owner_name' => $this->owner_name,
            'owner_phone' => $this->owner_phone,
            'checkin_time' => now(),
        ]);

        $this->notifyCheckin($vehicle);

        Notification::make()->title('Vehicle checked in successfully.')->success()->seconds(5)->send();

        $this->reset(['plate_number', 'owner_name', 'owner_phone']);
        $this->showNew = false;
    }

    protected function notifyCheckin(Vehicle $vehicle): void
    {
        try {
            $message = \App\Services\SmsTemplateService::formatCheckin($vehicle);

            $sender = new class {
                use \App\Traits\SendsSms;
                public function sendNow(string $phone, string $message): bool
                {
                    return $this->sendSms($phone, $message);
                }
            };
            $sender->sendNow((string) ($vehicle->owner_phone ?? ''), $message);
        } catch (\Throwable $e) {
            // silent
        }
    }

    public function checkoutVehicle(string $vehicleId): void
    {
        $vehicle = Vehicle::whereNull('checkout_time')->find($vehicleId);
        if (! $vehicle) {
            Notification::make()->title('No active check-in found for the selected vehicle.')->warning()->seconds(5)->send();
            return;
        }

        $vehicle->checkout_time = now();
        $vehicle->save();

        $this->notifyCheckout($vehicle);

        Notification::make()->title('Vehicle checked out successfully.')->success()->seconds(5)->send();
    }

    protected function notifyCheckout(Vehicle $vehicle): void
    {
        try {
            $payment = \App\Models\Payment::where('vehicle_id', $vehicle->id)
                ->orderByDesc('id')
                ->first();

            $message = \App\Services\SmsTemplateService::formatCheckout($vehicle, $payment);

            $sender = new class {
                use \App\Traits\SendsSms;
                public function sendNow(string $phone, string $message): bool
                {
                    return $this->sendSms($phone, $message);
                }
            };
            $sender->sendNow((string) ($vehicle->owner_phone ?? ''), $message);
        } catch (\Throwable $e) {
            // silent
        }
    }

    public function getVehiclesProperty()
    {
        $q = trim($this->query);
        if ($q === '') {
            return collect();
        }

        $agent = Agent::where('user_id', Auth::id())->latest()->first();
        $query = Vehicle::query()->whereNull('checkout_time');
        if ($agent?->place_id) {
            $query->where('place_id', $agent->place_id);
        }
        $query->where(function ($sub) use ($q) {
            $sub->where('plate_number', 'like', "%{$q}%")
                ->orWhere('owner_phone', 'like', "%{$q}%");
        });
        return $query->latest('checkin_time')->limit(50)->get();
    }

    public function render()
    {
        return view('livewire.parking-desk');
    }
}
