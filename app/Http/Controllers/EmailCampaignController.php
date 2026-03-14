<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateEmailCampaignAction;
use App\Models\EmailCampaign;
use App\Models\MailList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class EmailCampaignController extends Controller
{
    public function index(): Response
    {
        $campaigns = EmailCampaign::query()
            ->where('organization_id', tenant('id'))
            ->with(['mailList'])
            ->latest()
            ->paginate(15);

        $mailLists = MailList::query()
            ->where('organization_id', tenant('id'))
            ->get(['id', 'name']);

        return Inertia::render('email-campaigns/index', [
            'campaigns' => $campaigns,
            'mailLists' => $mailLists,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'preview_text' => 'nullable|string|max:255',
            'html_content' => 'nullable|string',
            'from_name' => 'nullable|string|max:255',
            'from_email' => 'nullable|email|max:255',
            'mail_list_id' => 'nullable|exists:mail_lists,id',
            'scheduled_at' => 'nullable|date',
        ]);

        EmailCampaign::create([
            'organization_id' => tenant('id'),
            ...$validated,
        ]);

        return redirect()->route('email-campaigns.index')->with('success', 'Campaign created.');
    }

    public function personalise(Request $request, EmailCampaign $emailCampaign, GenerateEmailCampaignAction $action): JsonResponse
    {
        $recipientName = $request->string('recipient_name', 'Valued Client')->toString();
        $personalised = $action->handle($emailCampaign, $recipientName);

        return response()->json($personalised);
    }

    public function send(EmailCampaign $emailCampaign): RedirectResponse
    {
        if ($emailCampaign->status !== 'draft') {
            return back()->with('error', 'Only draft campaigns can be sent.');
        }

        $emailCampaign->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return redirect()->route('email-campaigns.index')->with('success', 'Campaign sent.');
    }

    public function destroy(EmailCampaign $emailCampaign): RedirectResponse
    {
        $emailCampaign->delete();

        return redirect()->route('email-campaigns.index')->with('success', 'Campaign deleted.');
    }
}
