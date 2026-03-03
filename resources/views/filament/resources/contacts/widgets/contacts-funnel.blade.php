<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Contacts by stage (funnel)
        </x-slot>
        <div class="space-y-2">
            @if (count($stageCounts ?? []) > 0)
                <div class="grid gap-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                    @foreach ($stageCounts as $stage => $count)
                        @php
                            $pct = $totalWithStage > 0 ? round((float) $count / $totalWithStage * 100, 1) : 0;
                        @endphp
                        <div class="rounded-lg border bg-muted/30 px-3 py-2">
                            <div class="text-sm font-medium text-muted-foreground">{{ $stage ?: '—' }}</div>
                            <div class="text-xl font-semibold">{{ number_format($count) }}</div>
                            <div class="text-xs text-muted-foreground">{{ $pct }}% of staged</div>
                        </div>
                    @endforeach
                </div>
                <p class="text-xs text-muted-foreground mt-1">Total contacts: {{ number_format($contactsTotal) }} ({{ number_format($totalWithStage) }} with stage)</p>
            @else
                <p class="text-sm text-muted-foreground">No contacts with stage set, or no data in this organization.</p>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
