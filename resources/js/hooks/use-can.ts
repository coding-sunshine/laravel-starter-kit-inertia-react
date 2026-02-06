import { usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';

/**
 * Check if the current user has the given permission(s).
 * Uses shared auth.permissions and auth.can_bypass (bypass-permissions = allow all).
 *
 * @param permission - One permission name, or array of names (true if user has any).
 */
export function useCan(permission: string | string[]): boolean {
    const { auth } = usePage<SharedData>().props;

    if (auth.can_bypass) {
        return true;
    }

    const permissions = auth.permissions ?? [];
    const check = Array.isArray(permission) ? permission : [permission];

    return check.some((p) => permissions.includes(p));
}
