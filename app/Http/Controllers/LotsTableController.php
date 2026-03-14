<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataTables\LotDataTable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class LotsTableController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('lots/index', LotDataTable::inertiaProps($request));
    }
}
