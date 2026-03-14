<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataTables\ProjectDataTable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ProjectsTableController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('projects/index', ProjectDataTable::inertiaProps($request));
    }
}
