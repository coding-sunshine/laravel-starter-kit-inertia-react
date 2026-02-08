# Api\V1\UserController (API)

## Purpose

CRUD, batch, and search for users on the versioned API. Uses **spatie/laravel-query-builder** for filter, sort, include, and sparse fields. Intended for API consumers (e.g. mobile, integrations); requires Sanctum auth. Success/error responses use **essa/api-tool-kit** format where applicable.

## Location

`app/Http/Controllers\Api\V1\UserController.php`

## Methods

| Method    | HTTP   | Route / name              | Purpose                                      |
|-----------|--------|---------------------------|----------------------------------------------|
| `index`   | GET    | `api/v1/users`            | Paginated user list (filter/sort/include/fields) |
| `show`    | GET    | `api/v1/users/{user}`     | Single user                                  |
| `store`   | POST   | `api/v1/users`            | Create user                                  |
| `update`  | PUT/PATCH | `api/v1/users/{user}`  | Update user                                  |
| `destroy` | DELETE | `api/v1/users/{user}`     | Delete user                                  |
| `batch`   | POST   | `api/v1/users/batch`      | Batch create/update/delete users             |
| `search`  | POST   | `api/v1/users/search`     | Search users (body: filters, sort, per_page, include) |

## Routes (auth:sanctum)

- `api.v1.users.index`: GET `api/v1/users`
- `api.v1.users.show`: GET `api/v1/users/{user}`
- `api.v1.users.store`: POST `api/v1/users`
- `api.v1.users.update`: PUT/PATCH `api/v1/users/{user}`
- `api.v1.users.destroy`: DELETE `api/v1/users/{user}`
- `api.v1.users.batch`: POST `api/v1/users/batch`
- `api.v1.users.search`: POST `api/v1/users/search`

## Query/body parameters

**Index (GET):** filter[name], filter[email], sort, include (e.g. roles), per_page, **fields[users]** (sparse fields: id, name, email, email_verified_at, created_at, updated_at).

**Search (POST body):** filters (name, email), sort (e.g. -created_at), per_page, page, include (e.g. ["roles"]).

**Batch (POST body):** create (array of { name, email, password }), update (array of { id, name?, email? }), delete (array of user ids). Policy checks apply per item.

## Response

- Index/search: `UserResource` collection (paginated); same envelope as Laravel API resources.
- Show/store/update: Toolkit success envelope with `data` (UserResource).
- Destroy: 204 No Content.
- Batch: Toolkit success with `data.created`, `data.updated`, `data.deleted` (arrays of ids).

## Related

- **Resource**: `App\Http\Resources\UserResource`
- **Model**: `App\Models\User`
- **Actions**: CreateUser, UpdateUser, DeleteUser
- **Form requests**: CreateUserRequest, UpdateUserRequest, DeleteUserRequest; Api\V1\BatchUserRequest, SearchUserRequest
- **API reference**: [routes.md](../api-reference/routes.md); [API response format](../api-reference/README.md#api-response-format); OpenAPI at `/docs/api`
