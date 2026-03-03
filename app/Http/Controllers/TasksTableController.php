<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataTables\TaskDataTable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TasksTableController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user() !== null, 403);

        return Inertia::render('task-table', [
            'tableData' => TaskDataTable::makeTable($request)->toArray(),
        ]);
    }
}
