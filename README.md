# GTA V Character Gallery

Fan-made fictional character photo gallery built with **PHP 8+**, **MySQL**, and **Bootstrap 5**.

> ⚠️ This is an unofficial fan site. All characters are fictional and belong to Rockstar Games.

---

## Requirements

- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- XAMPP / Laragon / any PHP development server
- phpMyAdmin (for DB import)

---

## Installation

### 1. Clone / Copy Files

Copy the project folder to your web server's document root:
- **XAMPP**: `C:\xampp\htdocs\wikifeet\`
- **Laragon**: `C:\laragon\www\wikifeet\`

### 2. Create Database

Open **phpMyAdmin** and:

1. Import `database/schema.sql` — creates the `wikifeet_gta` database and tables.
2. Import `database/seed.sql` — adds admin user, 3 sample characters, and 9 sample photos.

### 3. Configure

Edit `config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'wikifeet_gta');
define('DB_USER', 'root');       // your MySQL user
define('DB_PASS', '');           // your MySQL password
define('SITE_URL', 'http://localhost/wikifeet');  // no trailing slash
define('DONATE_URL', 'https://ko-fi.com/yourusername');
```

### 4. Start Server

Start Apache + MySQL from XAMPP/Laragon, then open:
- **Site**: [http://localhost/wikifeet/](http://localhost/wikifeet/)
- **Admin**: [http://localhost/wikifeet/admin/login.php](http://localhost/wikifeet/admin/login.php)

---

## Admin Login

| Username | Password |
|----------|----------|
| `admin`  | `admin123!` |

> 🔑 Change your password immediately after first login via **Admin → Password**.

---

## Features

### Public
- Legal 18+ warning at the top
- Photo grid with lazy loading & broken-image fallback
- Character pages (`/character.php?slug=...`)
- Search characters (exact → redirect, partial → suggestions)
- Single photo detail page
- Contact / takedown form
- Donate button (navbar)

### Admin Panel
- Dashboard with stats
- **Characters CRUD** + Quick Add (auto-slug with collision handling)
- **Photos CRUD** + single add + **Bulk Add** (see below)
- Contact requests management (open/close/delete)
- Change password
- CSRF protection on all forms

---

## Bulk Add Photos – How to Use

### Step 1: Paste
1. Go to **Admin → Photos → Bulk Add** tab
2. Select a **character** from the dropdown
3. Paste photo URLs in the textarea, one per line:

```
https://example.com/photo1.jpg
https://example.com/photo2.jpg | https://source.com/page | Caption text
# This line is a comment and will be ignored
```

4. Click **"Parse & Preview"**

### Step 2: Preview & Edit
You'll see a summary and editable table:

- **Stats**: Total, OK, Invalid, Duplicate, Exists counts
- **Preview**: First 6 valid image thumbnails
- **Editable table**: Each row's `image_url`, `source_url`, and `caption` are editable

**Bulk Actions:**
| Button | Action |
|--------|--------|
| Auto-fix All | Trim, remove quotes/brackets, fix scheme on all rows |
| Remove Invalid | Delete rows with invalid URLs |
| Keep First of Duplicates | Remove duplicate URLs (keep first occurrence) |
| Remove Exists (DB) | Remove rows already in the database |
| Re-Preview | Re-validate after manual edits |

**Per-Row Actions:**
- 🔮 **Auto-fix** — fix a single row
- ❌ **Remove** — remove a single row

**Auto-fix Example:**
```
Before: <"https://example.com/photo.jpg ">
After:  https://example.com/photo.jpg
```

### Step 3: Import
- Click **"Import X Photo(s)"** button
- Only `OK` status rows are inserted
- Uses a single database transaction (rollback on error)
- Shows result summary: inserted / skipped / invalid / removed

---

## File Structure

```
wikifeet/
├── config.php
├── index.php
├── character.php
├── search.php
├── photo.php
├── contact.php
├── assets/css/styles.css
├── database/
│   ├── schema.sql
│   └── seed.sql
├── lib/
│   ├── db.php
│   ├── auth.php
│   ├── csrf.php
│   └── helpers.php
├── partials/
│   ├── header.php
│   └── footer.php
├── admin/
│   ├── login.php
│   ├── dashboard.php
│   ├── characters.php
│   ├── photos.php
│   ├── requests.php
│   ├── change_password.php
│   ├── logout.php
│   └── partials/
│       ├── admin_header.php
│       └── admin_footer.php
└── README.md
```

---

## Security

- PDO prepared statements (SQL injection prevention)
- `htmlspecialchars()` output escaping (XSS prevention)
- CSRF tokens on all admin forms
- Session-based authentication with `password_hash` / `password_verify`
- Session regeneration on login
