<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataTables\CommissionDataTable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CommissionController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('commissions/index', CommissionDataTable::inertiaProps($request));
    }
}
