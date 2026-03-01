# UI Step 5: Google Maps API integration

**Goal:** Integrate the Google Maps JavaScript API wherever location or geography adds value: routes (stops, sequence), trips (path, start/end), geofences, locations/addresses, electrification (charging stations), and any screen where “where” matters. Use a single API key and reusable map components so the app can show maps consistently.

**References:** [Google Maps JavaScript API](https://developers.google.com/maps/documentation/javascript), [@react-google-maps/api](https://www.npmjs.com/package/@react-google-maps/api) or vanilla JS wrapper. Fleet routes: `fleet.routes.show`, `fleet.trips.show`, `fleet.geofences.*`, `fleet.locations.*`, `fleet.ev-charging-stations.*`, electrification plan, etc.

---

## 1. Prerequisites

- Google Cloud project with Maps JavaScript API enabled. API key created and restricted (e.g. by HTTP referrer for the app’s domains). No key in repo; use environment variable.
- UI-01 design system in place so map containers and overlays (e.g. info windows, controls) can use the same palette (#333333, #4348be, white) where applicable.

---

## 2. Configuration

- **Environment:** Add `VITE_GOOGLE_MAPS_API_KEY=your_key_here` to `.env`. Laravel Vite exposes `import.meta.env.VITE_GOOGLE_MAPS_API_KEY` to the client. Document in `.env.example` and in deployment docs. Do not commit the key.
- **Loading the API:** Load the Maps JavaScript API script once (e.g. in a provider or layout) with the key and optional libraries (e.g. `places`, `geometry` if needed). Use callback or promise to ensure map components render after the API is ready. Consider a `GoogleMapsProvider` or similar that loads the script and exposes `isLoaded` and `loadError`.

---

## 3. Where to use maps

| Area | Route / page | Use case |
|------|--------------|----------|
| **Routes** | `fleet.routes.show` | Show route line and ordered stops (waypoints). Optional: drag to reorder, optimize button (existing AI route optimization). |
| **Trips** | `fleet.trips.show` | Show trip path (polyline or sequence of points), start/end markers. |
| **Geofences** | `fleet.geofences.index` / show | List with optional small static map; show page: draw or display geofence polygon. |
| **Geofence events** | `fleet.geofence-events.index` | Optional map with event locations. |
| **Locations** | `fleet.locations.*` | Address or lat/lng; show on map in show/create/edit if location has coordinates. |
| **EV charging stations** | `fleet.ev-charging-stations.*` | Show stations on map; useful for electrification plan and “where can we charge?”. |
| **Electrification plan** | `fleet.electrification-plan.index` | Optional map of fleet depots and suggested charging locations. |
| **Fleet optimization** | `fleet.fleet-optimization.index` | Optional map for “right-sizing” or depot/area view. |
| **Driver / vehicle** | e.g. last known location | If telematics or location data exists, optional map on driver or vehicle show. |

Prioritise: **Routes show** (stops on map), **Trips show** (path), **Geofences** (polygon), **Locations** (pin). Then EV charging and electrification/optimization as needed.

---

## 4. Reusable components

- **Map container:** A React component that accepts `center` (lat/lng), `zoom`, `style` (height/width), and optional `children` (markers, polylines, polygons). Uses Google `Map` and `useMap` or equivalent. Handles loading and error state (e.g. “Map unavailable” if key missing or API fails).
- **Markers:** Support for one or many markers (e.g. route stops, charging stations). Custom marker icon optional (e.g. primary color #4348be pin).
- **Polyline:** For route path or trip path; color from design system (#4348be or #333333).
- **Polygon:** For geofence boundary; fill with light primary opacity; stroke #4348be.
- **Info window / popover:** When clicking a marker or stop, show a small popover with label and optional link. Style popover with white background and #333333 text so it matches the app.

---

## 5. Backend and data

- **Stored data:** Routes have stops (order, location); trips may have start/end or path; geofences have boundary (e.g. GeoJSON or lat/lng list); locations have address or lat/lng. Ensure API responses include coordinates where needed (e.g. `route_stops` with `latitude`, `longitude` or `location`). No schema change required if data already exists; only expose in API/Inertia props.
- **Permissions:** Map components are used only on authenticated fleet routes; no extra backend permission beyond existing fleet.* access.

---

## 6. Performance and limits

- Load the Maps API once per app (not per route). Lazy-load map component on pages that need it if bundle size is a concern.
- Respect Google Maps usage and quota; avoid unnecessary re-renders (e.g. memoize map options and markers). Use static map image as fallback only if desired (different API).

---

## 7. Done when

- `VITE_GOOGLE_MAPS_API_KEY` is documented and used; Maps JavaScript API loads in the app.
- At least one fleet page (e.g. Route show or Trip show) displays a map with relevant data (stops or path). Reusable map component(s) exist for markers, polyline, polygon.
- Geofences, locations, or EV charging stations use the map where specified; info windows/overlays use design system colors where applicable.

After UI-05, the UI/UX refactor roadmap is complete: design system, login, app shell with Fleet sidebar, Fleet dashboard, and Google Maps where needed. Further polish (e.g. more pages with maps, advanced animations) can follow the same tokens and patterns.

---

## 8. Where to test (with seed data)

Run the Fleet seeder so locations have coordinates and one route has stops and one trip has waypoints:

```bash
php artisan db:seed --class=Database\\Seeders\\Development\\FleetFullSeeder
```

(Or run the full dev seed: `php artisan db:seed` if your `DatabaseSeeder` includes the Development category.)

| Page | URL | What you should see |
|------|-----|----------------------|
| **Trip show (with path)** | **Fleet → Trips → click "view" on the first trip** (e.g. `/fleet/trips/1`) | Map with "Trip path": a **polyline** London → Birmingham → Manchester and **Start** / **End** markers. If the trip has no waypoints, you see the map with "No path recorded for this trip." |
| **Route show (with stops)** | **Fleet → Routes → click the first route name or "view"** (e.g. `/fleet/routes/1`) | Map with "Route map": **numbered markers** (1, 2, 3) for each stop (HQ Depot, South Hub, North Yard). Only routes whose stops use **locations with lat/lng** show the map. |

**Note:** The **Route edit** page (`/fleet/routes/1/edit`) and the **Trips list** (`/fleet/trips`) do not show a map; maps are only on the **Trip detail** and **Route detail** (show) pages.
