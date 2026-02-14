<x-filament-panels::page>
    <x-filament-widgets::widgets
        :widgets="$this->getHeaderWidgets()"
        :columns="$this->getHeaderWidgetsColumns()"
    />

    <div class="mt-6">
        <x-filament::section>
            <x-slot name="heading">
                Product analytics (Pan)
            </x-slot>
            <x-slot name="description">
                Privacy-focused impressions, hovers, and clicks for elements with <code class="text-xs bg-muted px-1 py-0.5 rounded">data-pan</code>. Application-wide; not scoped by organization.
            </x-slot>

            @php
                $analytics = $this->getAnalytics();
            @endphp

            @if ($analytics->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No analytics recorded yet. Add <code class="bg-muted px-1 py-0.5 rounded">data-pan="name"</code> to key UI elements (tabs, buttons, nav links). View in terminal: <code class="bg-muted px-1 py-0.5 rounded">php artisan pan</code>.
                </p>
            @else
                <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-white/5 dark:bg-gray-800">
                    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 dark:divide-white/5">
                        <thead class="divide-y divide-gray-200 dark:divide-white/5">
                            <tr class="bg-gray-50 dark:bg-white/5">
                                <th class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">Name</th>
                                <th class="fi-ta-header-cell px-3 py-3.5 text-end text-sm font-semibold text-gray-950 dark:text-white">Impressions</th>
                                <th class="fi-ta-header-cell px-3 py-3.5 text-end text-sm font-semibold text-gray-950 dark:text-white">Hovers</th>
                                <th class="fi-ta-header-cell px-3 py-3.5 text-end text-sm font-semibold text-gray-950 dark:text-white">Clicks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @foreach ($analytics as $row)
                                <tr class="fi-ta-row">
                                    <td class="fi-ta-cell px-3 py-3.5 text-sm text-gray-950 dark:text-white"><code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs dark:bg-white/10">{{ $row->name }}</code></td>
                                    <td class="fi-ta-cell px-3 py-3.5 text-end tabular-nums text-sm text-gray-950 dark:text-white">{{ number_format($row->impressions) }}</td>
                                    <td class="fi-ta-cell px-3 py-3.5 text-end tabular-nums text-sm text-gray-950 dark:text-white">{{ number_format($row->hovers) }}</td>
                                    <td class="fi-ta-cell px-3 py-3.5 text-end tabular-nums text-sm text-gray-950 dark:text-white">{{ number_format($row->clicks) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
