<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BrochureMailJob;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class MailStatusController extends Controller
{
    public function index(Request $request): Response
    {
        $jobs = BrochureMailJob::query()
            ->orderByDesc('created_at')
            ->paginate(25);

        return Inertia::render('mail-status/index', [
            'jobs' => $jobs,
        ]);
    }
}
