<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Multi-Organization Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, the application operates in multi-organization (tenant)
    | mode. Users can belong to multiple organizations and switch between them.
    | When disabled, the application operates in single-tenant mode.
    |
    */
    'enabled' => env('MULTI_ORGANIZATION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Tenant Term
    |--------------------------------------------------------------------------
    |
    | The user-facing term used for organizations. This is used in UI text
    | and error messages. Common options: 'organization', 'team', 'workspace',
    | 'company', 'account'.
    |
    */
    'term' => env('TENANT_TERM', 'Organization'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Term (Plural)
    |--------------------------------------------------------------------------
    |
    | The plural form of the tenant term used in UI text.
    |
    */
    'term_plural' => env('TENANT_TERM_PLURAL', 'Organizations'),

    /*
    |--------------------------------------------------------------------------
    | Allow User Organization Creation
    |--------------------------------------------------------------------------
    |
    | When enabled, users can create new organizations. When disabled,
    | only admins can create organizations for users.
    |
    */
    'allow_user_organization_creation' => env('ALLOW_USER_ORGANIZATION_CREATION', true),

    /*
    |--------------------------------------------------------------------------
    | Default Organization Name
    |--------------------------------------------------------------------------
    |
    | The default name for the personal organization created when a user
    | registers. Use {name} as a placeholder for the user's name.
    |
    */
    'default_organization_name' => env('DEFAULT_ORGANIZATION_NAME', "{name}'s Workspace"),

    /*
    |--------------------------------------------------------------------------
    | Auto-Create Personal Organization
    |--------------------------------------------------------------------------
    |
    | When enabled, a personal organization is automatically created for
    | each new user during registration.
    |
    */
    'auto_create_personal_organization' => env('AUTO_CREATE_PERSONAL_ORGANIZATION', true),

    /*
    |--------------------------------------------------------------------------
    | Domain & Subdomain Resolution
    |--------------------------------------------------------------------------
    |
    | Base domain for subdomain-based tenant resolution. When host is
    | {slug}.{domain} (e.g. acme.example.com), the organization with that slug
    | is set as current tenant. Set to null to disable subdomain resolution.
    |
    */
    'domain' => env('TENANCY_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Subdomain Resolution
    |--------------------------------------------------------------------------
    |
    | When true, requests to {slug}.{tenancy.domain} resolve to the organization
    | with that slug. When false, only verified organization_domains are used.
    |
    */
    'subdomain_resolution' => env('TENANCY_SUBDOMAIN_RESOLUTION', true),

    /*
    |--------------------------------------------------------------------------
    | Invitation Settings
    |--------------------------------------------------------------------------
    */
    'invitations' => [
        'expires_in_days' => env('INVITATION_EXPIRES_IN_DAYS', 7),
        'allow_registration' => env('INVITATION_ALLOW_REGISTRATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sharing Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for cross-organization data sharing.
    |
    */
    'sharing' => [
        // Restrict sharing to only "connected" organizations
        'restrict_to_connected' => env('SHARING_RESTRICT_TO_CONNECTED', false),

        // What happens when shared data is edited:
        // 'original_owner' - edits belong to original org, shared org loses access on revocation
        // 'copy_on_edit' - any edit creates a copy for the editing org
        'edit_ownership' => env('SHARING_EDIT_OWNERSHIP', 'original_owner'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Super-Admin Settings
    |--------------------------------------------------------------------------
    |
    | Settings for super-admin bypass and cross-organization access.
    |
    */
    'super_admin' => [
        // Allow super-admins to view all organizations' data
        'can_view_all' => env('SUPER_ADMIN_CAN_VIEW_ALL', true),

        // Session key for super-admin view-all mode
        'view_all_session_key' => 'view_all_organizations',
    ],

];
