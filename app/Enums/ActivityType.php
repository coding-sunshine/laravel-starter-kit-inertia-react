<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Activity log description values. Use these when logging custom events
 * so descriptions stay consistent and IDE-friendly.
 */
enum ActivityType: string
{
    case RolesAssigned = 'roles_assigned';
    case RolesUpdated = 'roles_updated';
    case PermissionsAssigned = 'permissions_assigned';
    case PermissionsUpdated = 'permissions_updated';
    case TwoFactorEnabled = 'two_factor_enabled';
    case TwoFactorDisabled = 'two_factor_disabled';
    case TwoFactorConfirmed = 'two_factor_confirmed';
    case RecoveryCodesRegenerated = 'recovery_codes_regenerated';
    case RoleCreated = 'role_created';
    case RoleUpdated = 'role_updated';
    case PermissionCreated = 'permission_created';
    case PermissionUpdated = 'permission_updated';
    case ImpersonationStarted = 'impersonation_started';
    case ImpersonationEnded = 'impersonation_ended';
}
