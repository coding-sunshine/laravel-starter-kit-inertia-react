<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataTables\PropertySearchDataTable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PropertySearchController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('searches/index', PropertySearchDataTable::inertiaProps($request));
    }
}
