<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\StoreEnterpriseInquiryAction;
use App\Http\Requests\StoreEnterpriseInquiryRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class EnterpriseInquiryController
{
    public function create(): Response
    {
        return Inertia::render('enterprise-inquiries/create');
    }

    public function store(
        StoreEnterpriseInquiryRequest $request,
        StoreEnterpriseInquiryAction $action,
    ): RedirectResponse {
        /** @var array{name: string, email: string, company?: string, phone?: string, message: string} $data */
        $data = $request->safe()->only(['name', 'email', 'company', 'phone', 'message']);

        $action->handle($data);

        return to_route('enterprise-inquiries.create')
            ->with('status', 'Thank you. We will be in touch soon.');
    }
}
