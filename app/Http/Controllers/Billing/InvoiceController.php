<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Models\Billing\Invoice;
use App\Services\TenantContext;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final readonly class InvoiceController
{
    public function index(): Response
    {
        $organization = TenantContext::get();
        abort_unless($organization, 403, 'No organization selected.');
        $invoices = $organization->invoices()->paginate(15);

        return Inertia::render('billing/invoices', [
            'invoices' => $invoices,
        ]);
    }

    public function download(Invoice $invoice): HttpResponse
    {
        $organization = TenantContext::get();
        abort_if(! $organization || $invoice->organization_id !== $organization->id, 403);

        return response('Invoice PDF not yet implemented.', 501);
    }
}
