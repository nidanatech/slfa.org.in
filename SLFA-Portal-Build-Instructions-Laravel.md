# SLFA Community Portal — Build Instructions (Laravel + Vite)

**Stack:** Laravel (existing project) + Vite (existing) + MySQL/Postgres + Laravel Breeze (auth) + Filament (admin panel) + Laravel Storage (local → S3-compatible later) + Razorpay PHP SDK for payments.

**Existing asset base:** `https://slfa.org.in/assets/img/` — reuse everything here rather than regenerating. Full inventory below.

---

## 0. Asset Inventory (pull these into the Laravel project first)

Download and store under `storage/app/public/legacy/` (served via `php artisan storage:link`), then reference from `public/storage/legacy/...` in Blade/Vite views:

- **Logo:** `assets/img/logo.png` (header + footer), `assets/img/footer.png` (footer variant), `assets/img/srilankan-friendship-logo.png` (hero/about mark)
- **Decorative:** `assets/img/contact/shape-2.png` (background behind News & Activities section)
- **Management — Present Set up (4 photos):**
  - `assets/img/home/team/present/dharmalingam.jpg` — Bose Dharmalingam, President
  - `assets/img/home/team/present/solomans.jpg` — Issac Navaneethan Solomans, VP
  - `assets/img/home/team/present/aminuddin.jpg` — Syed Mohamed Aminuddin, Secretary
  - `assets/img/home/team/present/yogaratnam.jpg` — Kumudhini J. Yogaratnam, Treasurer
- **Management — Executive Committee (6 photos):**
  - `assets/img/home/team/committee/roche.jpg` — J T Roche
  - `assets/img/home/team/committee/naresh.jpg` — Naresh Julian
  - `assets/img/home/team/committee/prasath.jpg` — K.S.Sri Giriprasath
  - `assets/img/home/team/committee/arumugam.jpg` — Dr. A. Ravisankar
  - `assets/img/home/team/committee/vincent.jpg` — Richard Allen Vincent
  - `assets/img/home/team/committee/prakash.jpg` — S. Prakash
- **Membership form preview images:** `assets/img/Business-Membership-form.png`, `assets/img/Individual-Membership-form.png`
- **Membership PDFs (source data to convert into web forms):** `assets/img/CCF06102023.pdf` (Business), `assets/img/membership_form.pdf` (Individual)
- **Gallery (41 photos + 1 video):** `assets/img/gallery/gallery1.jpg` through `gallery41.jpg`, plus `assets/img/gallery/galleryvideo.mp4`

**Step:** Write a one-off Artisan command (`php artisan slfa:import-legacy-assets`) that HTTP-downloads all of the above into `storage/app/public/legacy/...` and inserts rows into the `gallery_media` and `committee_members` tables (§3, §6) pointing at the new local paths. Run once, then delete the command.

---

## 1. Project Setup (on top of your existing Laravel + Vite app)

1. `composer require laravel/breeze --dev` → `php artisan breeze:install blade` (or `vue`/`react` if your Vite setup already uses one — match what you have) to scaffold auth views wired to your existing Vite build.
2. `composer require filament/filament` → `php artisan filament:install --panels` for the admin dashboard (this replaces the entire custom "Admin Dashboard" build from the old plan — Filament generates CRUD screens from Eloquent models automatically).
3. `php artisan storage:link` so `storage/app/public` is web-accessible at `/storage`.
4. Confirm your `.env` DB connection (MySQL or Postgres — whichever your current hosting provides).
5. Pull the existing site's CSS variables (primary blue/maroon accent, fonts) from slfa.org.in's stylesheet into your Tailwind/Vite config so the rebuild still feels like SLFA, not a generic template.
6. `composer require razorpay/razorpay` for payments (§4).

---

## 2. Database Schema (Laravel migrations)

Core tables — everything else builds on these. Use `php artisan make:model X -m` for each.

```
members
  id, name (auth via Breeze's users table — extend or link via user_id)
  full_name, email, phone
  membership_type   enum('individual','business')
  sri_lanka_connection text   -- origin/ancestral/trade/education/spouse
  occupation, company_name, designation
  city
  photo_path
  status  enum('pending','active','expired','rejected')
  directory_visible  boolean default(true)
  joined_at, renewed_at, timestamps

committee_members
  id, member_id (nullable fk -> members)
  name, role (President/VP/Secretary/Treasurer/Executive Member)
  company, designation
  photo_path          -- seeded from legacy assets above
  tier  enum('present','executive')
  display_order, timestamps

events
  id, title, description, event_type enum('AGM','Get-Together','Medical Camp','Eye Camp','Health Checkup','Other')
  location, start_at, end_at
  cover_image_path
  rsvp_enabled boolean default(true)
  timestamps

event_rsvps
  id, event_id (fk), member_id (fk), status enum('going','maybe','declined'), timestamps

news_posts
  id, title, body (longtext), category, cover_image_path, published_at, author_id (fk users), timestamps

gallery_albums
  id, title, event_id (nullable fk), cover_image_path, timestamps

gallery_media
  id, album_id (fk), path, type enum('image','video'), caption, sort_order, timestamps
  -- seeded from legacy gallery1.jpg ... gallery41.jpg + galleryvideo.mp4

payments
  id, member_id (fk), amount, purpose enum('admission','annual_renewal','donation'),
  razorpay_order_id, razorpay_payment_id, status, timestamps

documents
  id, title, file_path, category enum('bylaws','minutes','registration','other'), uploaded_at, timestamps

contact_messages
  id, name, email, phone, message, read boolean default(false), timestamps
```

Add `role` (enum `member`/`admin`) to Breeze's default `users` table via a migration — this drives Filament panel access.

---

## 3. Feature: Public Marketing Pages (replaces the current single-page site)

Build as standard Laravel Blade views (or Vue/React components if your Vite setup is SPA-style) with routes in `routes/web.php`:

1. **Home** (`/`) — hero using `srilankan-friendship-logo.png`, mission paragraphs (copy verbatim from current About text — it's accurate bylaws language, don't let anything rewrite it), CTA buttons → "Become a Member" (`/join`) and "Member Login" (`/login`).
2. **About** (`/about`) — founding story (Feb 2015 Deputy High Commission dinner → registered 22 May 2019, Reg. No. 234/2019 under TN Society Registration Act 1975). Static Blade view, copy as-is.
3. **News & Activities** (`/news`) — Eloquent-driven from `news_posts`, filterable by category query param (AGM / Get-Together / Medical Camp / Health Checkup) — replaces the four static hardcoded lists.
4. **Management** (`/management`) — queries `committee_members` ordered by `tier`, `display_order`. Seed with the 4 "Present Set up" + 6 executive members and their photos listed in §0.
5. **Contact** (`/contact`) — address (`164/27B, Dr. P. J. Thomas Avenue, Injambakkam, Chennai-600115`), phone (`+91 98840 88808`), email (`info@slfa.org.in`), same social links as current footer (Facebook, X/Twitter, YouTube), plus a form (`POST /contact`) writing to `contact_messages` and triggering a `Mail::to(config('mail.committee'))` notification.
6. **Footer partial** (`resources/views/partials/footer.blade.php`) — reuse `footer.png` mark, quick links, same social URLs, included on every page.

---

## 4. Feature: Authentication & Membership Onboarding

1. Breeze gives you `/register`, `/login`, `/forgot-password` out of the box — extend the registration flow (or add a dedicated `/join` multi-step form) with membership-specific fields sourced directly from the two existing PDFs: name, DOB/age (18+ validation rule), Sri Lanka connection type (dropdown), residency confirmation (South India), membership type toggle (Individual ₹5,000 admission + ₹3,000/yr, or Business — double both).
2. On submit → create `members` row with `status = 'pending'` linked to the new `users` row; fire a Laravel event/listener that emails the committee for approval (mirrors the bylaw: "any two Executive Committee members including President or Secretary" approve).
3. **Admin > Pending Approvals** — a Filament resource on the `members` table filtered to `status = 'pending'`, with one-click Approve/Reject actions (Filament actions handle this in a few lines, no custom UI needed).
4. On approval → `Mail` notification with a payment link (`/pay/admission/{member}`).
5. **Razorpay checkout:** create an order server-side via the SDK, render Razorpay's checkout.js on the payment page, verify the payment signature in a webhook/controller, then mark `status = 'active'` and generate a member ID (e.g. `SLFA-{year}-{id}`).
6. **Annual renewal:** a scheduled Artisan command (`php artisan schedule` entry, run daily) that finds members whose `renewed_at` is approaching 31 March and emails a renewal link — same Razorpay flow, `purpose = 'annual_renewal'`.

---

## 5. Feature: Member Directory (core "portal" feature — build this carefully)

This is the single highest-value addition since it turns the site from brochure into network.

1. **Data:** query `members` where `status = 'active'` and `directory_visible = true`. Members can toggle visibility off anytime from their profile settings page (`/profile`, extending Breeze's default profile view).
2. **Directory page** (`/directory`, behind Breeze's `auth` middleware — members only):
   - Grid/list of member cards: photo, name, city, occupation/company, membership-type badge. Use Laravel pagination (`->paginate(24)`) — don't load the whole table at once.
   - **Search:** a simple `LIKE` query on name/company for now; if the member base grows past a few hundred, swap in Laravel Scout + a lightweight driver (database driver is free, no external service needed).
   - **Filters:** city, profession/industry, membership type (individual/business), Sri Lanka connection type — implement as query-string filters on the Eloquent query (`when()` clauses keep this clean).
   - **Sort:** newest members, alphabetical.
3. **Member profile page** (`/directory/{member}`): fuller bio, company, designation, connection to Sri Lanka, and a "Message" button that opens an in-app contact form rather than exposing raw email/phone by default. Add a per-member `show_contact_info` boolean so members opt in to displaying direct contact details if they want.
4. **Business directory sub-view** (`/directory?type=business`): filters to `membership_type = 'business'` — becomes a mini B2B directory of Sri Lanka-connected businesses in South India, directly serving the association's stated purpose of members gaining from each other's "profession, trade, business and expertise."
5. **Admin controls (Filament):** a toggle on the `members` resource to feature/pin certain members (e.g., committee members) at the top of directory queries — add a `featured` boolean and order by it first.
6. **Privacy/consent:** an explicit checkbox at signup — "List me in the member directory" (default checked, but must be an active, documented choice). Store `directory_consent_at` timestamp on the `members` table so you have an audit trail.

---

## 6. Feature: Events Module

1. Filament resource for `events` — admin creates events; cover image can reuse a past `gallery_*.jpg` for a similar recurring event, or a fresh upload via Filament's file upload field (auto-saves to `storage/app/public`).
2. Public `/events` list + `/events/{event}` detail page, with an RSVP button for logged-in members (`POST /events/{event}/rsvp`) writing to `event_rsvps`.
3. Scheduled command sends a reminder email 3 days before `start_at` to everyone with `status = 'going'`.
4. Past events link to their `gallery_albums` entry (§7) — the event detail page shows "Photos from this event" once an album is tagged to it.
5. Seed historical events straight from the current site copy so institutional history isn't lost in the rebuild: 4 AGMs (2021, 2022, 2023 + upcoming), 4 Get-Togethers (2016, 2018 ×2, 2019), Tsunami Day Medical/Eye Camp (Dec 2015, Devaneri Village), Free Health Checkup (March 2019, Dr. Rela Institute).

---

## 7. Feature: Gallery (albums, not a flat dump)

1. Import all 41 legacy photos + video into `gallery_media`, initially under a single "Legacy Gallery" album (created by the import command in §0) so nothing is lost on launch.
2. As a low-priority admin task, re-tag photos into the correct `gallery_albums` entry per event once the committee identifies which photos belong to which AGM/Get-Together/Camp — a simple Filament bulk-action ("Move to album") makes this fast.
3. New uploads going forward always attach to an `event_id` at upload time via the Filament form — this permanently fixes the "flat unlabeled block" problem.
4. Front-end lightbox viewer (a lightweight Alpine.js component, since Laravel ships with Alpine via Breeze) with next/prev, captions, and download link.
5. Video (`galleryvideo.mp4`) gets its own `type = 'video'` entry with an HTML5 `<video>` tag and a poster frame pulled from the first gallery image.

---

## 8. Feature: Admin Dashboard (Filament — mostly configuration, not custom code)

Filament auto-generates list/create/edit screens per resource. Build these Filament Resources:

1. `MemberResource` — pending approvals filter/action (§4), directory-visibility override, featured toggle
2. `NewsPostResource` — create/edit/publish
3. `EventResource` — create/edit, RSVP toggle, attendee list as a relation manager
4. `GalleryAlbumResource` + `GalleryMediaResource` — bulk upload, tag-to-event bulk action
5. `CommitteeMemberResource` — add/remove, swap photo, reorder via `display_order`
6. `DocumentResource` (§9)
7. `PaymentResource` — read-only log with filters by purpose/status
8. A custom Filament page for the Broadcast tool (§10) using a simple form + `Mail::send` loop

Gate the Filament panel to `role = 'admin'` via `canAccessPanel()` on the User model.

---

## 9. Feature: Document Library

1. `documents` table + `storage/app/public/documents/` for files, managed via `DocumentResource` in Filament.
2. Categories: Bylaws, AGM Minutes, Registration Certificate, Other.
3. Seed with what's already public: society registration reference (No. 234/2019, TN Society Registration Act 1975) as a document entry once the committee provides the certificate PDF.
4. Public route `/documents` behind `auth` middleware — members-only view + download.

---

## 10. Feature: Announcements / Broadcast Tool

1. Filament custom page: admin composes a message, selects audience (all active members / specific city / specific membership type) via a query on `members`.
2. Sends via Laravel's built-in `Mail` (any SMTP provider, or a transactional service like Postmark/SES if volume grows) — no extra backend needed since this all runs inside Laravel.
3. Optional: a "Copy for WhatsApp" button that formats the message as plain text for manual paste into a WhatsApp group, avoiding WhatsApp Business API costs entirely unless the committee wants full automation later.

---

## 11. Feature: Discussion Board / Notice Board (Phase 2)

1. Two more tables: `board_threads` (title, body, category, member_id, timestamps) and `board_replies` (thread_id, member_id, body, timestamps).
2. Categories: General, Business Referrals, Ask the Community, Events Feedback.
3. Simple Blade + Alpine views, `auth` middleware — no Filament needed here since this is member-facing, not admin.
4. Directly serves the stated mission — "congregate... for mutual acquaintance and help."

---

## 12. Build Order (recommended sequence)

1. Breeze + Filament install on your existing Laravel/Vite app, migrations for all tables (§1–2)
2. Legacy asset import command (§0) — run once, seeds Management + Gallery + Documents data
3. Public marketing pages using existing copy/assets verbatim (§3) — ship first, zero data-modeling risk, immediate visual win
4. Auth + membership application + admin approval via Filament (§4)
5. Razorpay payments wired to admission + renewal (§4)
6. Member directory (§5) — the core differentiator
7. Events + RSVP (§6)
8. Gallery migration + albums (§7)
9. Remaining Filament resources consolidated into the admin panel (§8)
10. Documents (§9), Broadcast tool (§10)
11. Discussion board (§11) — only after the above is stable and adopted

---

## Notes on continuity
- Keep all existing body copy (About, Eligibility, Fees) verbatim at launch — it's accurate legal/procedural language from their bylaws; don't let an AI rewrite it.
- Keep the same social links (Facebook, Twitter/X, YouTube) and contact details exactly as currently published.
- Old PDFs (`membership_form.pdf`, `CCF06102023.pdf`) should stay downloadable even after the web form ships, for anyone who prefers paper — move them into the Documents library (§9) rather than deleting them.
- Because everything runs inside your existing Laravel app, there's no separate backend service, no second auth system, and no monthly platform fee beyond your current hosting + Razorpay's per-transaction fee (~2%).
