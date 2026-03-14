<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataTables\PropertyEnquiryDataTable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PropertyEnquiryController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('enquiries/index', PropertyEnquiryDataTable::inertiaProps($request));
    }
}
