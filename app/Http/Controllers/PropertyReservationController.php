<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataTables\PropertyReservationDataTable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PropertyReservationController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('reservations/index', PropertyReservationDataTable::inertiaProps($request));
    }
}
