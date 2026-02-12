import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
    /** Permission names for the current user (empty when guest). Use with useCan() or <Can>. */
    permissions: string[];
    /** Role names for the current user (empty when guest). */
    roles: string[];
    /** True when user has bypass-permissions (e.g. super-admin). useCan() treats as allowed for any permission. */
    can_bypass: boolean;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
    /** Permission name(s) required to show this item (user must have any). Omit to show to all authenticated users. */
    permission?: string | string[];
    /** Feature flag key (e.g. 'blog'). Item is hidden when this feature is inactive. */
    feature?: string;
}

/** Pennant feature flags shared to the frontend (key => active for current user/guest default). */
export interface SharedFeatures {
    api_access?: boolean;
    appearance_settings?: boolean;
    blog?: boolean;
    changelog?: boolean;
    contact?: boolean;
    cookie_consent?: boolean;
    gamification?: boolean;
    help?: boolean;
    impersonation?: boolean;
    onboarding?: boolean;
    personal_data_export?: boolean;
    profile_pdf_export?: boolean;
    registration?: boolean;
    scramble_api_docs?: boolean;
    two_factor_auth?: boolean;
    [key: string]: boolean | undefined;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    /** Feature flags (guest = default value, authenticated = resolved for user). */
    features: SharedFeatures;
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
    avatar_profile?: string | null;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}
