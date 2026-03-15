<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PotentialProperty;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PotentialPropertyController extends Controller
{
    public function index(Request $request): Response
    {
        $properties = PotentialProperty::query()
            ->orderByDesc('created_at')
            ->paginate(25)
            ->through(fn (PotentialProperty $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'suburb' => $p->suburb,
                'state' => $p->state,
                'developer_name' => $p->developer_name,
                'estimated_price_min' => $p->estimated_price_min,
                'estimated_price_max' => $p->estimated_price_max,
                'status' => $p->status,
                'created_at' => $p->created_at?->toISOString(),
            ]);

        return Inertia::render('potential-properties/index', [
            'properties' => $properties,
        ]);
    }
}
