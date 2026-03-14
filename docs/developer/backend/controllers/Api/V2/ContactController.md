# Api\V2\ContactController (API v2)

## Purpose

CRUD for contacts on the v2 API. Uses **spatie/laravel-query-builder** for filters/sorts. Scoped to the current organization via `TenantContext`. Requires Sanctum auth + `tenant` middleware.

## Location

`app/Http/Controllers/Api/V2/ContactController.php`

## Methods

| Method   | HTTP       | Route                       | Purpose                      |
|----------|------------|-----------------------------|------------------------------|
| `index`  | GET        | `api/v2/contacts`           | Paginated contact list       |
| `show`   | GET        | `api/v2/contacts/{contact}` | Single contact               |
| `store`  | POST       | `api/v2/contacts`           | Create contact               |
| `update` | PUT/PATCH  | `api/v2/contacts/{contact}` | Update contact               |

## Query Parameters (index)

`filter[first_name]`, `filter[last_name]`, `filter[type]`, `filter[stage]`, `sort`, `per_page`

## Related

- **Resource**: `App\Http\Resources\Api\V2\ContactResource`
- **Model**: `App\Models\Contact`
- **Route names**: `api.v2.contacts.*`
