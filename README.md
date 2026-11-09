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
- **Theme (Admin > Theme):** pick Primary, Secondary and Tertiary colors, button and button-text colors, header, footer, body-text and link colors, plus font and corner style. Five presets, a live preview, readability (contrast) warnings and a reset button.
- **Page sections:** every page can also use the home page's building blocks (hero, listings, call to action, photo + text, team, video and more) above or below its text.
- **Pages (Admin > Pages):** create any number of pages (About, Buyer guide, Seller guide...) with a visual editor (headings, lists, links, button links, image upload, YouTube/Vimeo embeds, HTML source mode), a URL name, draft/published status and SEO title and description. Drafts are visible only to the logged-in admin.
- **Navigation (Admin > Navigation):** header menus can have dropdown sub-items (hover, click, keyboard and mobile). Edit the header menu and footer links: add, remove, rename, hide, open in a new tab, and drag to reorder. Items can point to a site section, a page, or any custom link.
- **SEO:** per-photo alt text, per-page title/description, and an XML sitemap at `/sitemap.php` (submit it in Google Search Console).
- **Social links:** footer links for Facebook, Instagram, YouTube, LinkedIn, X, TikTok.
- **IDX/MLS:** Settings has an IDX embed field. Paste the iframe/script from your IDX vendor and a "Search All MLS" page appears (also covered by the registration gate).

## Forgot the admin password?
- With shell access: `php reset_admin.php "new-password-10+chars" [admin@email.com]`.
- Without it: create an empty file `data/RESET_ADMIN` using your host's file manager or FTP, open `/reset_admin.php` in your browser and choose a new password. The file deletes itself afterwards, and the page is switched off (404) whenever that file is absent.

## About MLS / IDX
Full MLS search can't be built from scratch: listing data comes from the board (e.g. TRREB) through an approved IDX/VOW vendor, under board rules (disclaimers, display requirements). Get the embed snippet or feed from your vendor and paste it in Settings. If you need listings stored inside this site's own database (custom search pages), ask the vendor for an RESO Web API / feed and it can be imported into the `properties` table.

## Pages, menus and URLs
Page content is cleaned on save (an allow-list: no scripts, inline styles or event handlers; iframes only from YouTube and Vimeo), so pasted content cannot break or attack the site. Pages live at `/page.php?slug=about`. For prettier `/p/about` URLs, switch on **Pretty page URLs** in Settings (needs Apache `mod_rewrite`; the bundled `.htaccess` does the rewrite). The Log in / Register links are added to the header automatically when visitor registration is on.

## Browser support
Targets the current and two previous major versions of Chrome, Edge, Firefox and Safari (desktop, Android and iOS). The CSS/JS used (`aspect-ratio`, `clamp()`, flex/grid `gap`, `DataTransfer`) has been available since roughly Chrome/Edge 88, Firefox 89 and Safari 15, so it sits well inside that window. No build step or polyfills. Notes:
- Drag-and-drop photo ordering uses a mouse/pen; on touch screens use the arrow buttons on each photo.
- Uploaded video: MP4 (H.264) plays everywhere; WebM is fine on current browsers but MP4 is the safest choice for Safari/iOS.
- Checked in headless Chrome at phone and desktop widths. Please also spot-check Safari and Firefox before launch.

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
index.php listings.php robots.php property.php contact.php idx.php page.php sitemap.php
register.php login.php logout.php searches.php send_alerts.php
admin/   login, setup, dashboard, properties, property_edit, home, pages, page_edit, navigation, theme, seo, tools, leads, settings, upload
includes/ bootstrap.php (DB, helpers) cms.php (theme, sanitizer, menus) seo.php home.php search.php header.php footer.php
assets/  css, js, img      uploads/  data/
```
