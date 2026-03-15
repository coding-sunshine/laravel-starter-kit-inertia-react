<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FinanceAssessment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class FinanceAssessmentController extends Controller
{
    public function index(Request $request): Response
    {
        $assessments = FinanceAssessment::query()
            ->orderByDesc('created_at')
            ->paginate(25);

        return Inertia::render('finance-assessments/index', [
            'assessments' => $assessments,
        ]);
    }
}
