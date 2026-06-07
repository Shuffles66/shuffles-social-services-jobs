# Advanced Ads — migrating banners from shuffles.com.au → justtasks.com.au

This is a **runbook** for copying your existing Advanced Ads banners from the Shuffles site to Just
Tasks. Advanced Ads is a third-party WordPress plugin; its ads, groups and placements live in each
site's own database, so "copying" them is a WordPress export/import — not something this plugin does
automatically. Once the ads exist on Just Tasks, the Shuffles SSJ plugin can display them (see
**Settings → Ads** and the `[sssj_ad]` shortcode — added in v0.89.0).

> ⚠️ **Do this on a quiet moment and take a backup first.** Importing creates posts and downloads
> images into Just Tasks. Nothing here deletes anything on Shuffles.

---

## What actually needs to move

| Item | Where it lives | Moves with… |
|---|---|---|
| **Ads** (the banners, code, image refs) | `advanced_ads` custom post type | WP export **or** Advanced Ads export |
| **Ad images** | Media Library (attachments) | "Download and import file attachments" on import |
| **Groups** | Advanced Ads data | **Advanced Ads** export (not plain WP export) |
| **Placements** (slugs you map to slots) | Advanced Ads option, not a post | **Advanced Ads** export, or re-create by hand |

**Takeaway:** prefer **Advanced Ads' own Import/Export** if your version has it — it carries ads +
groups + placements together. Plain WordPress export only carries the ad posts.

---

## Option A — Advanced Ads' own Import/Export (preferred)

1. **On shuffles.com.au** (wp-admin): go to **Advanced Ads → Tools → Import & Export** (menu wording
   varies by version; some have a dedicated *Import & Export* add-on).
2. Click **Export** and download the file (XML/JSON).
3. **On justtasks.com.au**: make sure the **Advanced Ads plugin is installed and active** (same major
   version is safest).
4. Go to the same **Advanced Ads → Tools → Import & Export** page → **Import** → upload the file.
5. Tick **import images / attachments** if offered.
6. Verify: **Advanced Ads → Ads / Groups / Placements** all came across.

## Option B — WordPress native export/import (ads only)

Use this if your Advanced Ads version has no built-in export. It brings the **ads** but **not the
placements** (you re-create those by hand — there are usually only a few).

1. **shuffles.com.au → Tools → Export** → choose **Ads** (the `advanced_ads` post type) if listed, or
   **All content** → **Download Export File** (a `.xml` WXR file).
2. **justtasks.com.au → Plugins → Add New** → install & activate **WordPress Importer**.
3. **Tools → Import → WordPress → Run Importer** → upload the `.xml`.
4. On the mapping screen: assign the author to an existing Just Tasks admin, and **tick "Download and
   import file attachments"** so the banner images come across.
5. **Re-create placements**: Advanced Ads → Placements → add the same placement slugs you use, and
   point each at the imported ad/group.

## Option C — WP-CLI (for whoever has shell access to both Cloudways apps)

```bash
# On the SOURCE (shuffles.com.au) app:
wp export --post_type=advanced_ads --dir=/home/master/ads-export   # writes a WXR .xml

# Copy the .xml to the TARGET (justtasks) app (scp/sftp), then on justtasks:
wp plugin install advanced-ads --activate          # if not already active
wp plugin install wordpress-importer --activate
wp import /path/to/advanced_ads.*.xml --authors=create
```
Notes:
- WP-CLI export of the CPT carries the **ads**; **placements/groups** still come from Advanced Ads'
  own export (Option A) or are re-created (Option B step 5).
- Banner **images**: `wp import` pulls referenced attachments when reachable; otherwise re-upload.

---

## After the ads are on Just Tasks — show them in the marketplace

1. **Advanced Ads → Placements**: note the **placement slug** (or an ad **id**) you want to show.
2. **wp-admin → Shuffles SSJ → Settings → Ads**:
   - Confirm it says **"Advanced Ads detected — active"**.
   - Leave **"Show Advanced Ads in the marketplace"** ticked.
   - Map a placement to a slot — **Board — top**, **Board — bottom**, or **Single listing**
     (value = the placement slug, or `id:123` for a specific ad).
3. Or place ads manually anywhere with the shortcode:
   - `[sssj_ad placement="your-slug"]` · `[sssj_ad id="123"]` · `[sssj_ad group="4"]`
4. **Check it renders** on a board / single listing. If a slot is blank, the slug is wrong or the
   master switch / Advanced Ads is off — nothing breaks, it just shows nothing.

---

## Gotchas

- **Same plugin version** on both sites avoids import format mismatches.
- **Ad code that references shuffles.com.au URLs** (images, links) should be updated to Just Tasks or
  to absolute URLs that still resolve.
- **Network/AdSense ads** (e.g. Google AdSense) carry their own account/site rules — re-verify the new
  domain in your ad network before expecting paid ads to serve.
- **Participant pages are never used for ad targeting** (see the Cookie & Consent Policy); keep ad
  slots on public boards/listings, not participant requests.

*Maintenance: update if Advanced Ads changes its export tools or if slot mappings change. Last updated: 2026-06-07.*
