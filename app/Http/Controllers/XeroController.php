<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ReconcileXeroPaymentAction;
use App\Http\Integrations\Xero\XeroConnector;
use App\Jobs\SyncContactToXeroJob;
use App\Jobs\SyncInvoiceToXeroJob;
use App\Models\Contact;
use App\Models\Sale;
use App\Models\XeroConnection;
use App\Models\XeroReconciliation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Handles Xero OAuth connection, contact/invoice sync, and payment webhook reconciliation.
 */
final class XeroController extends Controller
{
    public function index(): InertiaResponse
    {
        $connection = XeroConnection::query()->whereNull('disconnected_at')->latest()->first();

        $xeroContactsCount = 0;
        $xeroInvoicesCount = 0;

        if ($connection) {
            $xeroContactsCount = $connection->xeroContacts()->count();
            $xeroInvoicesCount = $connection->xeroInvoices()->count();
        }

        $recentReconciliations = XeroReconciliation::query()
            ->with('xeroInvoice')
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('xero/index', [
            'connection' => $connection,
            'xero_contacts_count' => $xeroContactsCount,
            'xero_invoices_count' => $xeroInvoicesCount,
            'reconciliations_count' => XeroReconciliation::query()->count(),
            'recent_reconciliations' => $recentReconciliations,
            'is_configured' => XeroConnector::isConfigured(),
        ]);
    }

    public function connect(): RedirectResponse
    {
        if (! XeroConnector::isConfigured()) {
            Log::warning('xero.deferred: XERO_CLIENT_ID not configured — OAuth connect deferred');

            return redirect()->back()->with('flash', [
                'type' => 'warning',
                'message' => 'Xero integration is not yet configured. Set XERO_CLIENT_ID and XERO_CLIENT_SECRET to enable OAuth.',
            ]);
        }

        $clientId = config('services.xero.client_id');
        $redirectUri = url(config('services.xero.redirect_uri', '/xero/callback'));
        $scopes = 'openid profile email accounting.transactions accounting.contacts offline_access';
        $state = csrf_token();

        $oauthUrl = 'https://login.xero.com/identity/connect/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scopes,
            'state' => $state,
        ]);

        return redirect($oauthUrl);
    }

    public function callback(Request $request): RedirectResponse
    {
        $code = $request->string('code')->toString();
        $state = $request->string('state')->toString();

        if (empty($code)) {
            return redirect()->route('xero.index')->with('flash', [
                'type' => 'error',
                'message' => 'Xero OAuth callback missing authorization code.',
            ]);
        }

        Log::info('xero.callback: received authorization code', ['state' => $state]);

        XeroConnection::query()->create([
            'xero_tenant_id' => 'pending-'.$code,
            'xero_tenant_name' => 'Pending connection',
            'connected_at' => now(),
        ]);

        return redirect()->route('xero.index')->with('flash', [
            'type' => 'success',
            'message' => 'Xero connection initiated. Token exchange is deferred until credentials are fully configured.',
        ]);
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $connection = XeroConnection::query()->whereNull('disconnected_at')->latest()->first();

        if ($connection) {
            $connection->update(['disconnected_at' => now()]);
            $connection->delete();
        }

        return redirect()->route('xero.index')->with('flash', [
            'type' => 'success',
            'message' => 'Xero account disconnected.',
        ]);
    }

    public function webhook(Request $request): Response
    {
        $webhookKey = config('services.xero.webhook_key');
        $payload = $request->getContent();
        $signature = $request->header('x-xero-signature', '');

        if (! empty($webhookKey)) {
            $expected = base64_encode(hash_hmac('sha256', $payload, $webhookKey, true));

            if (! hash_equals($expected, (string) $signature)) {
                Log::warning('xero.webhook: invalid signature');

                return response('Unauthorized', Response::HTTP_UNAUTHORIZED);
            }
        }

        $data = $request->all();

        app(ReconcileXeroPaymentAction::class)->handle($data);

        return response('', Response::HTTP_OK);
    }

    public function syncContacts(Request $request): RedirectResponse
    {
        $connection = XeroConnection::query()->whereNull('disconnected_at')->latest()->first();

        if (! $connection) {
            return redirect()->route('xero.index')->with('flash', [
                'type' => 'error',
                'message' => 'No active Xero connection found.',
            ]);
        }

        $contacts = Contact::query()->latest()->limit(50)->get();

        foreach ($contacts as $contact) {
            SyncContactToXeroJob::dispatch($contact, $connection);
        }

        return redirect()->route('xero.index')->with('flash', [
            'type' => 'success',
            'message' => "Dispatched sync for {$contacts->count()} contacts.",
        ]);
    }

    public function syncInvoices(Request $request): RedirectResponse
    {
        $connection = XeroConnection::query()->whereNull('disconnected_at')->latest()->first();

        if (! $connection) {
            return redirect()->route('xero.index')->with('flash', [
                'type' => 'error',
                'message' => 'No active Xero connection found.',
            ]);
        }

        $sales = Sale::query()->latest()->limit(50)->get();

        foreach ($sales as $sale) {
            SyncInvoiceToXeroJob::dispatch($sale, $connection);
        }

        return redirect()->route('xero.index')->with('flash', [
            'type' => 'success',
            'message' => "Dispatched sync for {$sales->count()} sales.",
        ]);
    }
}
