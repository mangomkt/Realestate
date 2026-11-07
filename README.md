# Real Estate Site (PHP)

Responsive real estate website with an admin backend. PHP 8.1+ with the `pdo_sqlite` and `fileinfo` extensions (standard on most hosts). No database to set up: data lives in `data/site.sqlite`, created automatically.

## Install
1. Upload the `realestate` folder to your host (or its web root).
2. Make sure `data/` and `uploads/` are writable by PHP.
3. Visit `/admin/login.php`. The first visit asks you to create the admin account.
4. Go to **Settings** to add your name, contact details, social links, and registration options, then **Properties → Add property**.

Local test: `php -S localhost:8000` from this folder.

## What's included
- **Public:** home page with search, listings with filters, sorting and pagination, property pages with photo gallery, hosted video or YouTube/Vimeo, inquiry form, contact page, mobile menu.
- **Lead capture:** visitor registration and login. In Settings you can switch registration on/off and optionally require it after N free listing views (default 2). Registrations, inquiries and contact messages all land in **Leads** (CSV export) and are emailed to your contact address.
- **Admin:** dashboard, add/edit/delete properties, multiple photos, MP4/WebM upload, featured toggle, leads, settings, password change.
- **Social links:** footer links for Facebook, Instagram, YouTube, LinkedIn, X, TikTok.
- **IDX/MLS:** Settings has an IDX embed field. Paste the iframe/script from your IDX vendor and a "Search All MLS" page appears (also covered by the registration gate).

## About MLS / IDX
Full MLS search can't be built from scratch: listing data comes from the board (e.g. TRREB) through an approved IDX/VOW vendor, under board rules (disclaimers, display requirements). Get the embed snippet or feed from your vendor and paste it in Settings. If you need listings stored inside this site's own database (custom search pages), ask the vendor for an RESO Web API / feed and it can be imported into the `properties` table.

## Video uploads
Raise these in `php.ini` (or `.user.ini`) for large videos:
```
upload_max_filesize = 200M
post_max_size = 210M
max_execution_time = 120
```
YouTube/Vimeo links are lighter on bandwidth for long tours.

## Security notes
- Use HTTPS. Passwords are hashed, forms use CSRF tokens, uploads are type-checked and PHP execution is blocked in `uploads/` (Apache `.htaccess`; on Nginx add an equivalent rule and deny `data/` and `includes/`).
- If you can, move `data/` outside the web root and update `DB_FILE` in `includes/bootstrap.php`.
- Lead emails use PHP `mail()`. For reliable delivery, configure SMTP on your host.
- Back up `data/` and `uploads/`.

## Structure
```
index.php listings.php property.php contact.php idx.php
register.php login.php logout.php
admin/   login, dashboard, properties, property_edit, leads, settings
includes/ bootstrap.php (DB, helpers) header.php footer.php
assets/  css, js, img      uploads/  data/
```
