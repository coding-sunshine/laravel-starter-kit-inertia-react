<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataTables\PropertyEnquiryDataTable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class EnquiriesTableController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user() !== null, 403);

        return Inertia::render('enquiry-table', [
            'tableData' => PropertyEnquiryDataTable::makeTable($request)->toArray(),
        ]);
    }
}
