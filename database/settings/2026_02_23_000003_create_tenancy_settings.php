<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $addIfMissing = function (string $key, mixed $value, bool $encrypted = false): void {
            if (! $this->migrator->exists($key)) {
                $encrypted ? $this->migrator->addEncrypted($key, $value) : $this->migrator->add($key, $value);
            }
        };
        $addIfMissing('tenancy.term', config('tenancy.term', 'Organization'));
        $addIfMissing('tenancy.term_plural', config('tenancy.term_plural', 'Organizations'));
        $addIfMissing('tenancy.allow_user_org_creation', (bool) config('tenancy.allow_user_organization_creation', true));
        $addIfMissing('tenancy.default_org_name', config('tenancy.default_organization_name', "{name}'s Workspace"));
        $addIfMissing('tenancy.auto_create_personal_org', (bool) config('tenancy.auto_create_personal_organization', true));
        $addIfMissing('tenancy.invitation_expires_in_days', (int) config('tenancy.invitations.expires_in_days', 7));
        $addIfMissing('tenancy.invitation_allow_registration', (bool) config('tenancy.invitations.allow_registration', true));
        $addIfMissing('tenancy.sharing_restrict_to_connected', (bool) config('tenancy.sharing.restrict_to_connected', false));
        $addIfMissing('tenancy.sharing_edit_ownership', config('tenancy.sharing.edit_ownership', 'original_owner'));
        $addIfMissing('tenancy.super_admin_can_view_all', (bool) config('tenancy.super_admin.can_view_all', true));
    }
};
