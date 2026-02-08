<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\PersonalDataExport\Jobs\CreatePersonalDataExportJob;

final class PersonalDataExportController
{
    /**
     * Request a personal data export. The export is created asynchronously;
     * the user receives an email with a download link when it is ready.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();
        assert($user !== null);

        dispatch(new CreatePersonalDataExportJob($user));

        return redirect()
            ->back()
            ->with('status', 'Your data export has been queued. You will receive an email with a download link when it is ready.');
    }
}
