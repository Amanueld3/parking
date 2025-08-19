@php
    $rows = $vehicles instanceof \Illuminate\Support\Collection ? $vehicles : collect($vehicles);
@endphp

@if ($rows->isEmpty())
    <div class="text-sm text-gray-500 dark:text-gray-400">No results. Start typing to search…</div>
@else
    <!-- Unified card layout for all widths -->
    <div class="space-y-3">
        @foreach ($rows as $v)
            <div class="p-3 bg-white border border-gray-200 rounded-lg dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-base font-semibold text-gray-900 dark:text-gray-100">
                            {{ $v->plate_number }}
                        </div>
                        <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            <div>
                                <span class="font-medium text-gray-700 dark:text-gray-200">Owner:</span>
                                {{ $v->owner_name ?: '-' }}
                            </div>
                            <div>
                                <span class="font-medium text-gray-700 dark:text-gray-200">Phone:</span>
                                {{ $v->owner_phone ? '+251' . $v->owner_phone : '-' }}
                            </div>
                            <div class="flex flex-wrap mt-1 gap-x-4 gap-y-1">
                                <div>
                                    <span class="font-medium text-gray-700 dark:text-gray-200">Place:</span>
                                    {{ $v->place?->name ?: '-' }}
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700 dark:text-gray-200">Check-in:</span>
                                    {{ $v->checkin_time ? \Illuminate\Support\Carbon::parse($v->checkin_time)->timezone(config('app.timezone'))->format('Y-m-d H:i') : '-' }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="shrink-0">
                        <x-filament::button color="success" size="sm"
                            wire:click="checkoutVehicle('{{ $v->id }}')">
                            Checkout
                        </x-filament::button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
