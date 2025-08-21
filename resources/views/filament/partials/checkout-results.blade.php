@php
    $rows = $vehicles instanceof \Illuminate\Support\Collection ? $vehicles : collect($vehicles);
@endphp

@if (session('error'))
    <div
        class="px-3 py-2 mb-3 text-sm border rounded-lg border-danger-300 bg-danger-50 text-danger-800 dark:border-danger-900/50 dark:bg-danger-900/20 dark:text-danger-300">
        {{ session('error') }}
    </div>
@endif
@if (session('success'))
    <div
        class="px-3 py-2 mb-3 text-sm border rounded-lg border-success-300 bg-success-50 text-success-800 dark:border-success-900/50 dark:bg-success-900/20 dark:text-success-300">
        {{ session('success') }}
    </div>
@endif

@if ($rows->isEmpty())
    <div class="text-sm text-gray-500 dark:text-gray-400">No results. Start typing to search…</div>
@else
    <!-- Unified card layout for all widths -->
    <div class="space-y-3">
        @foreach ($rows as $v)
            <div class="p-3 bg-white border border-gray-200 rounded-lg dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <div
                            class="inline-flex items-center rounded-md border border-gray-200 bg-gray-50 px-2.5 py-1 text-sm font-semibold font-mono tracking-wider text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                            {{ strtoupper($v->plate_number) }}
                        </div>

                        <div class="min-w-0">
                            <div class="space-y-1 text-sm">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-user class="w-4 h-4 text-gray-400" />
                                    <span class="text-gray-600 whitespace-nowrap dark:text-gray-400">Owner</span>
                                    <span
                                        class="font-medium text-gray-900 dark:text-gray-100">{{ $v->owner_name ?: '-' }}</span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-phone class="w-4 h-4 text-gray-400" />
                                    <span class="text-gray-600 whitespace-nowrap dark:text-gray-400">Phone</span>
                                    <span
                                        class="font-medium text-gray-900 dark:text-gray-100">{{ $v->owner_phone ? '+251' . $v->owner_phone : '-' }}</span>
                                </div>

                                <div class="flex flex-wrap items-center gap-x-6 gap-y-1">
                                    <div class="flex items-center gap-2">
                                        <x-heroicon-o-map-pin class="w-4 h-4 text-gray-400" />
                                        <span class="text-gray-600 whitespace-nowrap dark:text-gray-400">Place</span>
                                        <span
                                            class="font-medium text-gray-900 dark:text-gray-100">{{ $v->place?->name ?: '-' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <x-heroicon-o-clock class="w-4 h-4 text-gray-400" />
                                        <span class="text-gray-600 whitespace-nowrap dark:text-gray-400">Check-in</span>
                                        <span class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $v->checkin_time ? \Illuminate\Support\Carbon::parse($v->checkin_time)->timezone(config('app.timezone'))->format('Y-m-d H:i') : '-' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="shrink-0">
                        <form method="POST" action="{{ route('payments.start', ['vehicle' => $v->id]) }}">
                            @csrf
                            <input type="hidden" name="amount" value="10" />
                            <button type="submit"
                                class="fi-btn relative inline-grid grid-flow-col items-center justify-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm outline-none ring-1 ring-primary-600/20 transition hover:bg-primary-500 focus-visible:ring-2 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-primary-600 dark:hover:bg-primary-500">
                                <x-heroicon-o-credit-card class="w-4 h-4" />
                                <span>Pay & Checkout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
