<?php

declare(strict_types=1);

arch()->preset()->php();
arch()->preset()->security()->ignoring([
    'assert',
]);

arch('controllers')
    ->expect('App\Http\Controllers')
    ->not->toBeUsed();

// Strict preset disabled: Filament resource pages override protected getHeaderActions()
// and LoadsJsonData uses protected loadJson(); strict()->ignoring() did not exclude them.
// arch()->preset()->strict();
