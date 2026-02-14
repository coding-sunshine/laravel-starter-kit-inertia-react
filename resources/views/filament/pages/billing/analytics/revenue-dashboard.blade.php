<x-filament-panels::page>
    <x-filament-widgets::widgets
        :widgets="$this->getHeaderWidgets()"
        :columns="$this->getHeaderWidgetsColumns()"
    />

    <div class="mt-6">
        <x-filament::section>
            <x-slot name="heading">
                Revenue Metrics
            </x-slot>
            <x-slot name="description">
                Key performance indicators for your subscription business
            </x-slot>

            <div class="prose dark:prose-invert max-w-none">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    This dashboard provides insights into your subscription revenue, growth trends, and customer behavior.
                    Use these metrics to make data-driven decisions about your pricing and product strategy.
                </p>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
