<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\InviteToOrganizationAction;
use App\Http\Requests\StoreInvitationRequest;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

final readonly class OrganizationInvitationController
{
    use AuthorizesRequests;

    public function store(StoreInvitationRequest $request, Organization $organization, InviteToOrganizationAction $action): RedirectResponse
    {
        $invitation = $action->handle(
            $organization,
            $request->string('email')->value(),
            $request->string('role')->value(),
            $request->user()
        );

        return back()->with('status', __('Invitation sent to :email.', ['email' => $invitation->email]));
    }

    public function destroy(Organization $organization, OrganizationInvitation $invitation): RedirectResponse
    {
        $this->authorize('delete', $invitation);

        abort_if($invitation->organization_id !== $organization->id, 404);

        $invitation->markAsCancelled();

        return back()->with('status', __('Invitation cancelled.'));
    }

    public function update(Organization $organization, OrganizationInvitation $invitation): RedirectResponse
    {
        $this->authorize('update', $invitation);

        abort_if($invitation->organization_id !== $organization->id, 404);

        if (! $invitation->canBeResent()) {
            return back()->withErrors(['invitation' => __('Invitation cannot be resent.')]);
        }

        $invitation->resend();
        $invitation->load('inviter');
        $existingUser = \App\Models\User::query()->where('email', $invitation->email)->first();
        if ($existingUser) {
            $existingUser->notify(new \App\Notifications\OrganizationInvitationNotification($invitation));
        } else {
            \App\Notifications\OrganizationInvitationNotification::sendToEmail($invitation);
        }

        return back()->with('status', __('Invitation resent.'));
    }
}
