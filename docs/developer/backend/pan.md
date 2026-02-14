# Pan (product analytics)

[Pan](https://github.com/panphp/pan) is a lightweight, privacy-focused PHP product analytics library used in this application to track **impressions**, **hovers**, and **clicks** on key UI elements. It does not collect personal data (no IP, user agent, or identifiers).

## How it works

- **Client:** Pan’s middleware injects a small JS library into HTML pages. Elements with a `data-pan="name"` attribute are tracked for view (impression), hover, and click. Events are batched and sent to the app.
- **Server:** Events are POSTed to `/pan/events` (route prefix configurable). Only the analytic **name** and **counters** are stored in the `pan_analytics` table.
- **Viewing data:** Run `php artisan pan` (optionally `--filter=name`) to see a table of analytics in the terminal.

## Usage

Add the `data-pan` attribute to any HTML element you want to track. **Names must contain only letters, numbers, dashes, and underscores.**

```html
<button data-pan="tab-profile">Profile</button>
<button data-pan="tab-settings">Settings</button>
<a href="/register" data-pan="auth-register-link">Sign up</a>
```

In React/Inertia, use the same attribute on components that render to DOM elements (e.g. `Button`, `Link`):

```tsx
<Button data-pan="auth-login-button">Log in</Button>
<Link href={register()} data-pan="welcome-register">Register</Link>
```

## Configuration

Configuration is done in `App\Providers\AppServiceProvider::configurePan()` using `Pan\PanConfiguration`:

- **Allowed analytics (whitelist):** `PanConfiguration::allowedAnalytics([...])` — only these names are stored. This is the default in this app to avoid abuse (e.g. arbitrary names from client HTML).
- **Max analytics:** `PanConfiguration::maxAnalytics(10000)` — cap the number of distinct analytics records.
- **Unlimited:** `PanConfiguration::unlimitedAnalytics()` — no cap (use with care).
- **Route prefix:** `PanConfiguration::routePrefix('internal-analytics')` — default is `pan`, so the events URL is `/pan/events`.

When adding new tracked elements, add their `data-pan` value to the `allowedAnalytics` array in `AppServiceProvider`; otherwise they will not be persisted.

## Artisan commands

| Command | Description |
|--------|-------------|
| `php artisan pan` | Show analytics table (optionally `--filter=name`) |
| `php artisan pan:flush` | Delete all analytics records |

## Database

Table: `pan_analytics` (created by Pan’s migration). Columns: `id`, `name`, `impressions`, `hovers`, `clicks`.

## Where Pan is used in this app

- **Settings layout:** Sidebar nav items (e.g. `settings-nav-profile`, `settings-nav-password`, `settings-nav-appearance`).
- **Appearance:** Theme toggle tabs (`appearance-tab-light`, `appearance-tab-dark`, `appearance-tab-system`).
- **Auth:** Login button, register button, “Sign up” / “Log in” links, forgot-password button (`auth-*`).
- **Welcome:** Header links (Dashboard, Log in, Register, Blog, Changelog, Help, Contact) when visible (`welcome-*`).
- **App sidebar (authenticated):** Main nav (Dashboard, Organizations, Billing, Blog, Changelog, Help, Contact) and footer (API docs, Repository, Documentation) (`nav-*`).
- **User dashboard:** Quick action buttons (Edit profile, Settings, Export profile PDF, Contact support, Email templates for super-admin, Product analytics for admin) and the Analytics card link (`dashboard-quick-*`, `dashboard-card-view-analytics`).

See `AppServiceProvider::configurePan()` for the full whitelist.

## Viewing analytics (hierarchy)

- **User dashboard (Inertia):** Users with **access admin panel** see a **Product analytics** quick action and an **Analytics** card that links to the admin Product Analytics page. This gives admins a direct path from the app dashboard to analytics.
- **Filament dashboard:** The **Product Analytics** stats widget appears on the Filament dashboard (`/admin`) for users with access: totals (impressions, clicks, hovers) and top element by clicks, each linking to the full Product Analytics page.
- **Filament → Analytics → Product Analytics** (`/admin/analytics/product`): Full page with a table of all tracked elements (name, impressions, hovers, clicks) and the same header stats. Description notes that data is **application-wide** (not scoped by organization).
- **Hierarchy:** Pan does not support tenant or organization scoping; analytics are **application-wide**. There is no separate org-level analytics view; all product analytics are in the admin panel. Only users with `access admin panel` (or `bypass-permissions`) can see the Product Analytics page, widget, and dashboard links.
