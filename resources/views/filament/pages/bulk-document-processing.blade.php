<x-filament-panels::page>
    <div class="grid gap-6">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-filament::card>
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <div class="flex h-8 w-8 items-center justify-center rounded-md bg-yellow-100">
                            <x-heroicon-o-clock class="h-5 w-5 text-yellow-600" />
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-500">Pending</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ $this->getTableQuery()->where('queue_status', 'pending')->count() }}
                        </p>
                    </div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <div class="flex h-8 w-8 items-center justify-center rounded-md bg-blue-100">
                            <x-heroicon-o-arrow-path class="h-5 w-5 text-blue-600" />
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-500">Processing</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ $this->getTableQuery()->where('queue_status', 'processing')->count() }}
                        </p>
                    </div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <div class="flex h-8 w-8 items-center justify-center rounded-md bg-green-100">
                            <x-heroicon-o-check-circle class="h-5 w-5 text-green-600" />
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-500">Completed</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ $this->getTableQuery()->where('queue_status', 'completed')->count() }}
                        </p>
                    </div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <div class="flex h-8 w-8 items-center justify-center rounded-md bg-red-100">
                            <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-red-600" />
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-500">Failed</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ $this->getTableQuery()->where('queue_status', 'failed')->count() }}
                        </p>
                    </div>
                </div>
            </x-filament::card>
        </div>

        <!-- Instructions -->
        <x-filament::card>
            <div class="flex">
                <div class="flex-shrink-0">
                    <x-heroicon-o-information-circle class="h-5 w-5 text-blue-400" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-gray-900">
                        How to use Bulk Document Processing
                    </h3>
                    <div class="mt-2 text-sm text-gray-700">
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Click "Upload Documents" to select multiple PDF, image, or document files</li>
                            <li>Choose the processing type (auto-detect recommended)</li>
                            <li>Monitor real-time processing status in the table below</li>
                            <li>Use bulk actions to approve or reject multiple documents at once</li>
                            <li>Files are processed in the background - you can continue working</li>
                        </ul>
                    </div>
                </div>
            </div>
        </x-filament::card>

        <!-- Main Table -->
        {{ $this->table }}
    </div>

    <script>
        // Auto-refresh every 5 seconds
        setInterval(() => {
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('$refresh');
            }
        }, 5000);
    </script>
</x-filament-panels::page>