<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTxrRequest;
use App\Models\Rake;
use App\Services\TxrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

final class TxrController extends Controller
{
    public function __construct(
        private TxrService $txrService
    ) {}

    /**
     * Store a new TXR record for the rake.
     */
    public function store(StoreTxrRequest $request, Rake $rake): RedirectResponse
    {
        $this->txrService->create($rake, $request->validated());

        return Redirect::back()
            ->with('success', 'TXR record created successfully.');
    }

    /**
     * Update the TXR record for the rake.
     */
    public function update(StoreTxrRequest $request, Rake $rake): RedirectResponse
    {
        $this->txrService->update($rake, $request->validated());

        return Redirect::back()
            ->with('success', 'TXR record updated successfully.');
    }
}
