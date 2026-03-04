<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Enums\Fleet\ContractorInvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreContractorInvoiceRequest;
use App\Http\Requests\Fleet\UpdateContractorInvoiceRequest;
use App\Models\Fleet\Contractor;
use App\Models\Fleet\ContractorInvoice;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class ContractorInvoiceController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', ContractorInvoice::class);
        $invoices = ContractorInvoice::query()->with('contractor')->latest('invoice_date')->paginate(15)->withQueryString();

        return Inertia::render('Fleet/ContractorInvoices/Index', [
            'contractorInvoices' => $invoices,
            'contractors' => Contractor::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => array_map(fn (ContractorInvoiceStatus $c): array => ['value' => $c->value, 'name' => $c->name], ContractorInvoiceStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ContractorInvoice::class);

        return Inertia::render('Fleet/ContractorInvoices/Create', [
            'contractors' => Contractor::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => array_map(fn (ContractorInvoiceStatus $c): array => ['value' => $c->value, 'name' => $c->name], ContractorInvoiceStatus::cases()),
        ]);
    }

    public function store(StoreContractorInvoiceRequest $request): RedirectResponse
    {
        $this->authorize('create', ContractorInvoice::class);
        ContractorInvoice::query()->create($request->validated());

        return to_route('fleet.contractor-invoices.index')->with('flash', ['status' => 'success', 'message' => 'Contractor invoice created.']);
    }

    public function show(ContractorInvoice $contractor_invoice): Response
    {
        $this->authorize('view', $contractor_invoice);
        $contractor_invoice->load('contractor');

        return Inertia::render('Fleet/ContractorInvoices/Show', ['contractorInvoice' => $contractor_invoice]);
    }

    public function edit(ContractorInvoice $contractor_invoice): Response
    {
        $this->authorize('update', $contractor_invoice);

        return Inertia::render('Fleet/ContractorInvoices/Edit', [
            'contractorInvoice' => $contractor_invoice,
            'contractors' => Contractor::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => array_map(fn (ContractorInvoiceStatus $c): array => ['value' => $c->value, 'name' => $c->name], ContractorInvoiceStatus::cases()),
        ]);
    }

    public function update(UpdateContractorInvoiceRequest $request, ContractorInvoice $contractor_invoice): RedirectResponse
    {
        $this->authorize('update', $contractor_invoice);
        $contractor_invoice->update($request->validated());

        return to_route('fleet.contractor-invoices.show', $contractor_invoice)->with('flash', ['status' => 'success', 'message' => 'Contractor invoice updated.']);
    }

    public function destroy(ContractorInvoice $contractor_invoice): RedirectResponse
    {
        $this->authorize('delete', $contractor_invoice);
        $contractor_invoice->delete();

        return to_route('fleet.contractor-invoices.index')->with('flash', ['status' => 'success', 'message' => 'Contractor invoice deleted.']);
    }
}
