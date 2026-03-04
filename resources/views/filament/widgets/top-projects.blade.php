<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Top Projects
        </x-slot>

        <x-slot name="description">
            By lot count
        </x-slot>

        <div class="space-y-3">
            @foreach($projects as $project)
                <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ $project['title'] }}
                        </p>
                        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ $project['stage'] }}</span>
                            <span>•</span>
                            <span>{{ $project['estate'] }}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $project['total_lots'] }} lots
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            ${{ number_format(($project['avg_price'] ?? 0) / 1000) }}K avg
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>