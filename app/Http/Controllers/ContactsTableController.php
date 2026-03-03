<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataTables\ContactDataTable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ContactsTableController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user() !== null, 403);

        return Inertia::render('contact-table', [
            'tableData' => ContactDataTable::makeTable($request)->toArray(),
        ]);
    }
}
