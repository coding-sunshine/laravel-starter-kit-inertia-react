/**
 * UserContext - Global user and organization context management
 * Manages current user, active siding, and role-based access
 */

import { usePage } from '@inertiajs/react';
import { createContext, ReactNode, use, useEffect, useState } from 'react';

export interface UserRole {
    name: string;
    permissions: string[];
}

export interface Organization {
    id: number;
    name: string;
    slug: string;
}

export interface Siding {
    id: number;
    code: string;
    name: string;
    location: string;
    organization_id: number;
}

export interface CurrentUser {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    roles: UserRole[];
    organization?: Organization;
    siding?: Siding;
    permissions: string[];
}

interface UserContextType {
    user: CurrentUser | null;
    isLoading: boolean;
    activeSiding: Siding | null;
    setActiveSiding: (siding: Siding | null) => void;
    hasPermission: (permission: string) => boolean;
    hasRole: (role: string) => boolean;
    canAccessSiding: (sidingId: number) => boolean;
}

const UserContext = createContext<UserContextType | undefined>(undefined);

interface UserContextProviderProps {
    children: ReactNode;
}

export function UserContextProvider({ children }: UserContextProviderProps) {
    const { auth } = usePage().props;
    const [isLoading, setIsLoading] = useState(true);
    const [user, setUser] = useState<CurrentUser | null>(null);
    const [activeSiding, setActiveSiding] = useState<Siding | null>(null);

    useEffect(() => {
        /* eslint-disable @eslint-react/hooks-extra/no-direct-set-state-in-use-effect -- sync user/siding from Inertia auth */
        // Set user from Inertia auth prop
        if (auth) {
            const currentUser = auth as CurrentUser;
            setUser(currentUser);
            setActiveSiding(currentUser.siding || null);
        }
        setIsLoading(false);
        /* eslint-enable @eslint-react/hooks-extra/no-direct-set-state-in-use-effect */
    }, [auth]);

    const hasPermission = (permission: string): boolean => {
        if (!user) return false;

        // Super admin has all permissions
        if (user.roles.some((role) => role.name === 'super_admin')) {
            return true;
        }

        // Check user-level permissions
        if (user.permissions.includes(permission)) {
            return true;
        }

        // Check role-based permissions
        return user.roles.some((role) => role.permissions.includes(permission));
    };

    const hasRole = (role: string): boolean => {
        if (!user) return false;
        return user.roles.some((r) => r.name === role);
    };

    const canAccessSiding = (sidingId: number): boolean => {
        if (!user) return false;

        // Super admin can access all sidings
        if (hasRole('super_admin')) {
            return true;
        }

        // Check if user has siding-level access
        if (user.siding?.id === sidingId) {
            return true;
        }

        // Check if user's organization contains this siding
        // (This would require additional context data from server)
        return false;
    };

    const value: UserContextType = {
        user,
        isLoading,
        activeSiding,
        setActiveSiding,
        hasPermission,
        hasRole,
        canAccessSiding,
    };

    return <UserContext value={value}>{children}</UserContext>;
}

/**
 * Hook to use UserContext
 */
export function useUser(): UserContextType {
    const context = use(UserContext);
    if (context === undefined) {
        throw new Error('useUser must be used within UserContextProvider');
    }
    return context;
}

/**
 * Hook to check if user has a specific permission
 */
export function usePermission(permission: string): boolean {
    const { hasPermission } = useUser();
    return hasPermission(permission);
}

/**
 * Hook to check if user has a specific role
 */
export function useRole(role: string): boolean {
    const { hasRole } = useUser();
    return hasRole(role);
}

/**
 * Hook to get the current user
 */
export function useCurrentUser(): CurrentUser | null {
    const { user } = useUser();
    return user;
}

/**
 * Hook to get the active siding context
 */
export function useActiveSiding(): Siding | null {
    const { activeSiding } = useUser();
    return activeSiding;
}

export default UserContext;
