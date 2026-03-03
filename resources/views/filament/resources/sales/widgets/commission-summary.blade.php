<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Commission summary
        </x-slot>
        <div class="space-y-3">
            <div class="text-2xl font-semibold">
                Total commissions: {{ \Illuminate\Support\Number::currency($totalCommission ?? 0, 'AUD') }}
            </div>
            @if (count($byAgent ?? []) > 0)
                <div>
                    <div class="text-sm font-medium text-muted-foreground mb-1">By sales agent (top 10)</div>
                    <ul class="divide-y rounded-lg border text-sm">
                        @foreach ($byAgent ?? [] as $name => $amount)
                            <li class="flex justify-between px-3 py-2">
                                <span>{{ $name }}</span>
                                <span class="font-medium">{{ \Illuminate\Support\Number::currency($amount, 'AUD') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
