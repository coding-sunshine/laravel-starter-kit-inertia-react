# Allow photo uploads up to 200 MB

The app allows defect and incident photos up to **200 MB** per file. The **413 Request Entity Too Large** error means Nginx (or PHP) is rejecting the request before it reaches Laravel. Configure all three layers below.

## 1. Laravel (already done)

- Defect and Incident form requests validate `photos.*` with `max:204800` (200 MB in KB).

## 2. Nginx – `client_max_body_size`

Increase the max request body size so Nginx accepts 200 MB uploads.

### If you use **Laravel Herd**

1. Open Herd → **Settings** (or Preferences).
2. Go to **Nginx** (or **Web** / server config).
3. If there is a field for custom config or “Additional Nginx config”, add:
   ```nginx
   client_max_body_size 200M;
   ```
4. Restart Nginx (Herd usually has a “Restart services” or similar).

**Or** add a custom Nginx config file for your app:

- Herd’s Nginx config is often under:
  - `~/Library/Application Support/Herd/config/nginx/`
- Add a `.conf` file (e.g. `upload-size.conf`) containing:
  ```nginx
  client_max_body_size 200M;
  ```
- Include it from the main server block if needed, then restart Nginx.

### If you use **Laravel Valet** (with Nginx)

1. Create or edit the Nginx config for your site, e.g.:
   ```bash
   # Example path; Valet may use a different layout
   ~/.config/valet/Nginx/laravel-starter-kit-inertia-react.test
   ```
2. Inside the `server { ... }` block add:
   ```nginx
   client_max_body_size 200M;
   ```
3. Run:
   ```bash
   valet restart
   ```

### If you use a **custom Nginx** install

Add inside the `server { ... }` block that serves your app:

```nginx
client_max_body_size 200M;
```

Then reload Nginx:

```bash
sudo nginx -t && sudo nginx -s reload
```

## 3. PHP – `upload_max_filesize` and `post_max_size`

PHP must accept at least 200 MB per file and for the whole POST body.

1. Find your `php.ini`:
   ```bash
   php --ini
   ```
   (Herd/Valet often use a specific php.ini under their app support or config directory.)

2. Set (or add) these lines; use at least 200 MB for uploads and a bit more for the whole request:
   ```ini
   upload_max_filesize = 200M
   post_max_size = 210M
   ```

3. Restart PHP-FPM (and Nginx if you changed it):
   - **Herd:** Restart services from the Herd UI.
   - **Valet:** `valet restart`
   - **System PHP-FPM:** e.g. `sudo systemctl restart php-fpm` (or your service name)

## 4. Verify

1. Restart Nginx and PHP (Herd/Valet “Restart” is usually enough).
2. Open **Fleet → Defects → New defect**.
3. Attach an image under 200 MB and submit.
4. You should no longer see **413 Request Entity Too Large**; the defect (and photo) should save.

If 413 persists, the request is still being limited by Nginx or PHP: double-check the `server` block that serves `laravel-starter-kit-inertia-react.test` and the `php.ini` that your web/PHP-FPM is using.
