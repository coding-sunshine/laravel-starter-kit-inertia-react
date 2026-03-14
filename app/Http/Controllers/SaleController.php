<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataTables\SaleDataTable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SaleController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('sales/index', SaleDataTable::inertiaProps($request));
    }
}
