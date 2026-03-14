<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataTables\LotDataTable;
use App\DataTables\ProjectDataTable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class MemberListingsController extends Controller
{
    public function index(Request $request): Response
    {
        $lotsProps = LotDataTable::inertiaProps($request);
        $projectsProps = ProjectDataTable::inertiaProps($request);

        return Inertia::render('member-listings/index', [
            'lotsTableData' => $lotsProps['tableData'] ?? null,
            'lotsSearchableColumns' => $lotsProps['searchableColumns'] ?? [],
            'projectsTableData' => $projectsProps['tableData'] ?? null,
            'projectsSearchableColumns' => $projectsProps['searchableColumns'] ?? [],
        ]);
    }
}
