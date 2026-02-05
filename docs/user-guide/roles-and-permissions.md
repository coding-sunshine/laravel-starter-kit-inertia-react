# Managing roles and permissions

If you have access to the admin panel, you can manage **roles** and **permissions** from **User management** in the sidebar.

## Roles

- **Roles** (`/admin/roles`): View, create, edit, and delete roles. Assign permissions to each role via the permissions checklist. Roles control what users can do (e.g. **admin** can access the panel and manage users; **user** has basic access).
- Default roles: **super-admin** (full access), **admin** (user management and panel access), **user** (standard access).

## Permissions

- **Permissions** (`/admin/permissions`): View all permissions. Use **Sync from routes** to create or update permissions from the application’s named routes (useful when route-based enforcement is enabled).
- Permissions are not created or edited by hand; they come from the sync command or from seeders.

## Assigning roles to users

When creating or editing a user in **Users** (`/admin/users`), use the **Roles** field to assign one or more roles. The user’s access is determined by the combined permissions of their roles.

For more technical details (route-based permissions, permission categories, super-admin bypass), see the [developer documentation](../developer/backend/permissions.md).
