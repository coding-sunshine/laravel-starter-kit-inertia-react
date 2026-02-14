# RemoveOrganizationMemberAction

## Purpose

Removes a member from an organization. If the member is the owner, transfers ownership to the first admin or deletes the organization if empty. Dispatches `OrganizationMemberRemoved` for billing/listener integration.

## Location

`app/Actions/RemoveOrganizationMemberAction.php`

## Method Signature

```php
public function handle(Organization $organization, User $member, ?User $removedBy = null): void
```

## Dependencies

None

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$organization` | `Organization` | The organization to remove the member from |
| `$member` | `User` | The user to remove |
| `$removedBy` | `?User` | Optional. The user who performed the removal (for audit). Pass `$request->user()` from the controller |

## Return Value

Void. The member is removed and ownership is transferred or the organization is deleted as needed.

## Usage Examples

### From Controller

```php
$action->handle($organization, $member, $request->user());
```

### From Job/Command

```php
app(RemoveOrganizationMemberAction::class)->handle($organization, $member, $removedBy);
```

## Related Components

- **Controller**: `OrganizationMemberController` (`destroy`)
- **Route**: `organizations.members.destroy`
- **Model**: `Organization`, `User`
- **Event**: `OrganizationMemberRemoved` (dispatched after removal; listeners such as `SyncSubscriptionSeatsOnMemberChange` react)

## Notes

- Uses `setPermissionsTeamId($organization->id)` before calling `getRoleNames()` so the correct org-scoped role is retrieved for `OrganizationMemberRemoved`.
- Owner removal triggers transfer to the first remaining member or organization deletion if empty.
