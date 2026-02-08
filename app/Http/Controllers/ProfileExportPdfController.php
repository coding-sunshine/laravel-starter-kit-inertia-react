<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use function Spatie\LaravelPdf\Support\pdf;

final class ProfileExportPdfController
{
    /**
     * Return the current user's profile as a PDF download.
     */
    public function __invoke(Request $request)
    {
        $user = $request->user();

        return pdf('pdf.profile', ['user' => $user])
            ->name('profile-'.now()->format('Y-m-d'))
            ->download();
    }
}
