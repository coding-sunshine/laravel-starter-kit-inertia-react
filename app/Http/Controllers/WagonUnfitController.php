<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreWagonUnfitRequest;
use App\Models\Txr;
use App\Services\WagonUnfitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

final class WagonUnfitController extends Controller
{
    public function __construct(
        private WagonUnfitService $wagonUnfitService
    ) {}

    /**
     * Store unfit wagon records for the TXR.
     */
    public function store(StoreWagonUnfitRequest $request, Txr $txr): RedirectResponse
    {
        $this->wagonUnfitService->store($txr, $request->validated());

        return Redirect::back()
            ->with('success', 'Unfit wagon details saved successfully.');
    }
}
