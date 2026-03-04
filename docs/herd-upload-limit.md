# Fix HTTP 413 (Upload Limit) with Laravel Herd

The AI Assistant allows uploads up to **100 MB**, but Laravel Herd’s default Nginx (and sometimes PHP) limits are around **1–2 MB**. That causes **HTTP 413** even for small files (e.g. 4 MB PDFs).

Do **one** of the following.

## Option 1: Herd UI (easiest)

1. Open **Laravel Herd**.
2. Go to **PHP** (or **Settings** → **PHP**).
3. Find **Maximum file upload size** (or similar) and set it to **100** MB.
4. Apply and restart Nginx/PHP if Herd asks.

If 413 persists, use Option 2.

## Option 2: Site-specific Nginx config

Herd only creates a **per-site** Nginx file when the site uses an isolated PHP version or is secured. Then you can set `client_max_body_size` for this project only.

### Step 1: Create the site Nginx file

In your project directory, run (use your PHP version if different):

```bash
herd isolate 8.3
```

This creates a config file at:

- **macOS:** `~/Library/Application Support/Herd/config/valet/Nginx/laravel-starter-kit-inertia-react.test`

### Step 2: Add the upload limit

Open that file and inside the `server { ... }` block (near the top, after the opening `server {`), add:

```nginx
client_max_body_size 100M;
```

Save the file.

### Step 3: Restart Nginx

In Herd, restart Nginx (or restart Herd). Or run:

```bash
herd restart nginx
```

(Use the exact command your Herd version provides.)

---

You can also run the project script that automates Step 1 and tries to patch the file:

```bash
./scripts/herd-fix-upload-limit.sh
```

Then restart Nginx from Herd if needed.
