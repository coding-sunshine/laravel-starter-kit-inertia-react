<?php

declare(strict_types=1);

namespace App\Http\Controllers\Indents;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class IndentsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('indents/index');
    }
}
