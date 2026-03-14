<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\PropertyReservation;
use App\Models\Sale;
use Inertia\Inertia;
use Inertia\Response;

final class FunnelController extends Controller
{
    public function index(): Response
    {
        $leads = Contact::query()->where('type', 'lead')->count();
        $prospects = Contact::query()->where('type', 'prospect')->count();
        $reservations = PropertyReservation::query()->count();
        $sales = Sale::query()->count();

        $stages = [
            ['label' => 'Leads', 'count' => $leads, 'key' => 'leads'],
            ['label' => 'Prospects', 'count' => $prospects, 'key' => 'prospects'],
            ['label' => 'Reservations', 'count' => $reservations, 'key' => 'reservations'],
            ['label' => 'Sales', 'count' => $sales, 'key' => 'sales'],
        ];

        return Inertia::render('funnel/index', [
            'stages' => $stages,
        ]);
    }
}
