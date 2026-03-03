<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ContactReportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless($request->user() !== null, 403);

        $byType = Contact::query()
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type');

        $byStage = Contact::query()
            ->selectRaw('stage, count(*) as count')
            ->groupBy('stage')
            ->pluck('count', 'stage');

        $bySource = Contact::query()
            ->selectRaw('source, count(*) as count')
            ->groupBy('source')
            ->pluck('count', 'source');

        $recentCount = Contact::query()
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->count();

        return Inertia::render('reports/contacts', [
            'byType' => $byType,
            'byStage' => $byStage,
            'bySource' => $bySource,
            'recentCount' => $recentCount,
        ]);
    }
}
