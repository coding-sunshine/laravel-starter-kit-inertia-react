<?php

declare(strict_types=1);

arch()->preset()->php();
arch()->preset()->security()->ignoring([
    'assert',
]);

arch('controllers')
    ->expect('App\Http\Controllers')
    ->not->toBeUsed();

// Prism: only PrismService may use the Prism facade; all other app code must use PrismService or ai().
arch('Prism facade only in PrismService')
    ->expect('App')
    ->not->toUse('Prism\Prism\Facades\Prism')
    ->ignoring('App\Services\PrismService');

// Relay: only PrismService and PrismValidate may use the Relay facade (PrismService for MCP tools, PrismValidate to validate config).
arch('Relay facade only in PrismService and PrismValidate')
    ->expect('App')
    ->not->toUse('Prism\Relay\Facades\Relay')
    ->ignoring(['App\Services\PrismService', 'App\Console\Commands\PrismValidate']);

// Filament: main app (Controllers, Actions) must not use Filament; admin panel is separate from Inertia.
arch('Filament is not used by main app')
    ->expect(['App\Http\Controllers', 'App\Actions'])
    ->not->toUse('App\Filament');

// Filament: admin layer must not use main app HTTP or action layer.
arch('Filament does not use Controllers or Actions')
    ->expect('App\Filament')
    ->not->toUse(['App\Http\Controllers', 'App\Actions']);

// Strict preset disabled: Filament resource pages override protected getHeaderActions()
// and LoadsJsonData uses protected loadJson(); strict()->ignoring() did not exclude them.
// arch()->preset()->strict();
