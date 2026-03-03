<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Task summary
        </x-slot>
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border bg-muted/30 px-4 py-3">
                <div class="text-sm font-medium text-muted-foreground">Open</div>
                <div class="text-2xl font-semibold">{{ number_format($open ?? 0) }}</div>
            </div>
            <div class="rounded-lg border bg-muted/30 px-4 py-3">
                <div class="text-sm font-medium text-muted-foreground">Overdue</div>
                <div class="text-2xl font-semibold {{ ($overdue ?? 0) > 0 ? 'text-danger-600' : '' }}">{{ number_format($overdue ?? 0) }}</div>
            </div>
            <div class="rounded-lg border bg-muted/30 px-4 py-3">
                <div class="text-sm font-medium text-muted-foreground">Completion rate</div>
                <div class="text-2xl font-semibold">{{ $completionRate ?? 0 }}%</div>
                <div class="text-xs text-muted-foreground">{{ number_format($completed ?? 0) }} of {{ number_format($total ?? 0) }} completed</div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
