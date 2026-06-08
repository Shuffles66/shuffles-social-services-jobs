# Asset rendering — Phase 2 (pixel-perfect server PDFs)

The "Create an asset" builder ([sssj_create_asset]) always works **for free** using the member's own
browser to make a PDF (the **Download PDF** / **Quick PDF (browser)** button) and a PNG (**Save image**).
That is the default and needs no setup.

Phase 2 adds an **optional** server-side renderer so résumés, service flyers and job flyers can be
produced as **print-quality, pixel-identical PDFs** for everyone, regardless of their browser. It is a
pluggable seam (`Shuffles_SSJ_Asset_Renderer`), default **off**.

## How it works

1. Member edits their wording and clicks **Print-quality PDF**.
2. The browser POSTs the asset type + edited wording to `admin-post.php?action=sssj_asset_render`
   (login + nonce + ownership checked).
3. The server **rebuilds the locked asset template** from the member's profile/job data, inlines the
   plugin CSS and any local images as data URIs into one standalone HTML document, and sends it to the
   configured render service.
4. The service returns a PDF, which is streamed back as a download. On any failure the member still has
   the free browser path.

Only the **server** holds the template + CSS, so the output is identical for every member.

## Set up the render service (Gotenberg — recommended)

Gotenberg is free, open source and self-hosted (a single container). On a server you control:

```
docker run --rm -p 3000:3000 gotenberg/gotenberg:8
```

Then in **WP Admin → Shuffles Jobs → Settings → Asset Rendering**:

- **Render quality** → *Server*
- **Render service URL** → `http://YOUR-SERVER-ADDRESS:3000` (must be reachable from the WordPress web
  server — a private/internal address or `http://127.0.0.1:3000` is ideal)
- **This render service is self-hosted / private** → tick it (required before any participant-derived
  asset could ever be rendered there)
- Click **Save**, then **Test connection** (generates a one-line test PDF through the service).

The plugin appends Gotenberg's conversion path (`/forms/chromium/convert/html`) automatically, so enter
the **base URL** only.

## Privacy

- A participant-derived asset is **never** sent to a renderer that has not been affirmed self-hosted.
  (No participant asset type exists yet; the guard is built in for when one does.)
- The render target URL is **admin-only configuration**, never user input.
- Keep the render service on your own infrastructure — member asset content is sent to it. Do **not**
  point it at a third-party HTML-to-PDF SaaS.

## Swapping the backend

The driver is filterable:

```php
add_filter( 'shuffles_ssj_asset_render_driver', fn() => 'my_driver' );
add_filter( 'shuffles_ssj_asset_render_custom', function ( $out, $html, $args, $driver ) {
    if ( 'my_driver' !== $driver ) { return $out; }
    // return [ 'ok' => bool, 'mime' => 'application/pdf', 'body' => $pdfBytes, 'error' => '' ]
}, 10, 4 );
```

## Scope

Phase 2 server rendering covers **PDF** for résumé, service flyer and job flyer (the print artifacts).
The social post + the self-promo graphic keep the browser PNG path, which already produces a good image.
A future phase can add server PNG (Gotenberg screenshot route) once square-sizing is tuned.
