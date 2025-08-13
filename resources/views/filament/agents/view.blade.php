<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            Agent Details
        </h3>
        <x-filament::badge color="{{ $record->status ? 'success' : 'danger' }}" size="md" class="font-medium">
            {{ $record->status ? 'Active' : 'Inactive' }}
        </x-filament::badge>
    </div>

    <!-- Details Grid -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <!-- Name Field -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Name
            </label>
            <div class="p-2 mt-1 text-sm text-gray-900 rounded-lg dark:text-gray-100 bg-gray-50 dark:bg-gray-800">
                {{ $user->name }}
            </div>
        </div>

        <!-- Email Field -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Email
            </label>
            <div class="p-2 mt-1 text-sm text-gray-900 rounded-lg dark:text-gray-100 bg-gray-50 dark:bg-gray-800">
                {{ $user->email }}
            </div>
        </div>

        <!-- Phone Field -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Phone
            </label>
            <div class="p-2 mt-1 text-sm text-gray-900 rounded-lg dark:text-gray-100 bg-gray-50 dark:bg-gray-800">
                {{ $user->phone ?: 'N/A' }}
            </div>
        </div>

        <!-- Created At Field -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Created At
            </label>
            <div class="p-2 mt-1 text-sm text-gray-900 rounded-lg dark:text-gray-100 bg-gray-50 dark:bg-gray-800">
                {{ $record->created_at->format('M j, Y g:i A') }}
            </div>
        </div>
    </div>

    <!-- Additional Info Section -->
    @if ($record->notes)
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Additional Notes
            </label>
            <div class="p-3 mt-1 text-sm text-gray-900 rounded-lg dark:text-gray-100 bg-gray-50 dark:bg-gray-800">
                {{ $record->notes }}
            </div>
        </div>
    @endif
</div>
