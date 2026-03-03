<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataTables\PropertyReservationDataTable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ReservationsTableController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user() !== null, 403);

        return Inertia::render('reservation-table', [
            'tableData' => PropertyReservationDataTable::makeTable($request)->toArray(),
        ]);
    }
}
