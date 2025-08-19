<div>
    <!-- Buttons (hidden on inline checkout pages) -->
    @unless ($hideButtons)
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-center gap-3">
                @can('create', \App\Models\Vehicle::class)
                    <a href="{{ \App\Filament\Resources\VehicleResource::getUrl('create') }}">
                        <x-filament::button color="primary" icon="heroicon-o-plus" tag="span">
                            New Vehicle
                        </x-filament::button>
                    </a>
                @endcan
                <a href="{{ \App\Filament\Pages\CheckoutParking::getUrl() }}">
                    <x-filament::button color="success" icon="heroicon-o-magnifying-glass" tag="span">
                        Checkout Parking
                    </x-filament::button>
                </a>
            </div>
            <div class="text-sm text-center text-gray-500 dark:text-gray-400">
                Use "New Vehicle" to check-in a vehicle. Use "Checkout Parking" to find an active check-in and checkout.
            </div>
        </div>
    @endunless

    <!-- New Vehicle Modal -->
    <x-filament::modal :visible="$showNew" width="2xl" close-by-clicking-away="true"
        x-on:close-modal.window="$wire.closeNewVehicle()">
        <x-slot name="heading">New Vehicle Check-in</x-slot>
        <form wire:submit.prevent="saveNewVehicle" class="space-y-4">
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label for="plate_number" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Plate
                        Number</label>
                    <input id="plate_number" type="text" wire:model.defer="plate_number"
                        class="block w-full px-3 py-2 mt-1 text-gray-900 bg-white border border-gray-300 rounded-lg dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:border-primary-600 focus:ring-primary-600" />
                    @error('plate_number')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="owner_name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Full
                        Name</label>
                    <input id="owner_name" type="text" wire:model.defer="owner_name"
                        class="block w-full px-3 py-2 mt-1 text-gray-900 bg-white border border-gray-300 rounded-lg dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:border-primary-600 focus:ring-primary-600" />
                    @error('owner_name')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="owner_phone"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-200">Phone</label>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-sm text-gray-600 dark:text-gray-300">+251</span>
                        <input id="owner_phone" type="text" wire:model.live="owner_phone" placeholder="9XXXXXXXX"
                            class="block w-full px-3 py-2 text-gray-900 bg-white border border-gray-300 rounded-lg dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:border-primary-600 focus:ring-primary-600" />
                    </div>
                    @error('owner_phone')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <x-filament::button color="gray" type="button"
                    wire:click="closeNewVehicle">Cancel</x-filament::button>
                <x-filament::button color="primary" type="submit">Check in</x-filament::button>
            </div>
        </form>
    </x-filament::modal>

    <!-- Checkout: modal or inline -->
    @if ($inlineCheckout)
        <div class="mt-8">
            <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-gray-100">Checkout Vehicle</h2>
            <div class="space-y-4">
                <div>
                    <label for="query" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Search by
                        Plate or Phone</label>
                    <input id="query" type="text" wire:model.live.debounce.300ms="query"
                        placeholder="e.g. ABC-123 or 9XXXXXXXX"
                        class="block w-full px-3 py-2 mt-1 text-gray-900 bg-white border border-gray-300 rounded-lg dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:border-primary-600 focus:ring-primary-600" />
                </div>

                @php($vehicles = $this->vehicles)
                @include('filament.partials.checkout-results', ['vehicles' => $vehicles])
            </div>
        </div>
    @else
        <x-filament::modal :visible="$showCheckout" width="3xl" close-by-clicking-away="true"
            x-on:close-modal.window="$wire.closeCheckout()">
            <x-slot name="heading">Checkout Vehicle</x-slot>
            <div class="space-y-4">
                <div>
                    <label for="query" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Search by
                        Plate or Phone</label>
                    <input id="query" type="text" wire:model.live.debounce.300ms="query"
                        placeholder="e.g. ABC-123 or 9XXXXXXXX"
                        class="block w-full px-3 py-2 mt-1 text-gray-900 bg-white border border-gray-300 rounded-lg dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:border-primary-600 focus:ring-primary-600" />
                </div>

                @php($vehicles = $this->vehicles)
                @include('filament.partials.checkout-results', ['vehicles' => $vehicles])
            </div>
            <x-slot name="footer">
                <div class="flex items-center justify-end gap-2">
                    <x-filament::button color="gray" type="button"
                        wire:click="closeCheckout">Close</x-filament::button>
                </div>
            </x-slot>
        </x-filament::modal>
    @endif
</div>
