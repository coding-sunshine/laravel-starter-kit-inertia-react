<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoaderOperatorRequest;
use App\Http\Requests\UpdateLoaderOperatorRequest;
use App\Models\LoaderOperator;
use Illuminate\Http\RedirectResponse;

final class LoaderOperatorsController extends Controller
{
    public function store(StoreLoaderOperatorRequest $request): RedirectResponse
    {
        LoaderOperator::query()->create([
            'name' => mb_trim((string) $request->validated('name')),
            'is_active' => $request->boolean('is_active', true),
            'siding_id' => $request->validated('siding_id'),
        ]);

        return redirect()
            ->route('master-data.loaders.index', ['tab' => 'operators'])
            ->with('success', 'Loader operator created successfully.');
    }

    public function update(UpdateLoaderOperatorRequest $request, LoaderOperator $loaderOperator): RedirectResponse
    {
        $loaderOperator->update([
            'name' => mb_trim((string) $request->validated('name')),
            'is_active' => $request->boolean('is_active'),
            'siding_id' => $request->validated('siding_id'),
        ]);

        return redirect()
            ->route('master-data.loaders.index', ['tab' => 'operators'])
            ->with('success', 'Loader operator updated successfully.');
    }
}
