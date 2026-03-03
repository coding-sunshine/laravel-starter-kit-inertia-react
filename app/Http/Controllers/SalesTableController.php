<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataTables\SaleDataTable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SalesTableController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user() !== null, 403);

        return Inertia::render('sale-table', [
            'tableData' => SaleDataTable::makeTable($request)->toArray(),
        ]);
    }
}
