# Cookie and Consent Policy

**Organisation:** {{Organisation / Platform name — e.g. Just Tasks}}
**Applies to:** all visitors and members of the platform — participants, their families, carers, nominees and advocates; workers, contractors and sole traders; organisations and their staff; and anyone else who interacts with us.
**Owner:** {{Role — e.g. Privacy Officer / Operations Manager}}
**Approved:** {{date}}  ·  **Version:** 1.0  ·  **Next review:** {{date — within 12 months}}

> **About this document.** This is a starting template aligned to the **Privacy Act 1988 (Cth)** and to **privacy-preserving defaults**. It is **not legal advice** — have it reviewed and formally adopted by your organisation before publishing, and tailor the bracketed details {{like this}} to your operation, the tools you actually run, and your contact points.

---

## 1. Purpose
This policy explains how the **{{Just Tasks}}** website uses cookies, local storage and similar technologies, the choices you have, and how we keep tracking to a minimum. It sits alongside our **Privacy Policy** (which covers personal information more broadly) and should be read with it.

## 2. What cookies and local storage are
- **Cookies** are small text files a website asks your browser to store, sent back to the server on later visits — used for things like keeping you logged in.
- **Local storage** (`localStorage` / `sessionStorage`) is a place in **your own browser** where the site can save small amounts of data. It is **not** sent to our server with every request, and we use it to remember your preferences on your device.
- **Similar technologies** include session identifiers and standard web-server logs. We treat all of these under this policy.

## 3. Our privacy-preserving stance
We aim to collect as little as possible and to **track as little as possible**.
- We **decline non-essential cookies by default** — nothing optional runs until you choose to allow it.
- Your **accessibility and display preferences** (including CALD / language and high-contrast settings), the **sidebar state**, and your **saved-search and UI state** are stored **in your browser (`localStorage`) for your own convenience** — on your device, under your control, and **not used to track you** or build a profile.
- We do not sell personal information, and we do not share it with advertisers for targeting (see section 6).

## 4. Cookie and storage categories

| Category | Purpose | Examples | Consent |
|---|---|---|---|
| **Strictly necessary** | Keep the site working and secure | Login / session cookie, CSRF / nonce security token, load-balancing | **No consent needed** — required for the site to function |
| **Functional / preferences** | Remember your choices, on your device | Accessibility & CALD settings, high-contrast / text-size, sidebar open/closed state, saved-search and board UI state (`localStorage`) | **No third-party consent needed** — stored **locally** and **member-controlled**; clearable any time |
| **Analytics** {{if used}} | Understand how the platform is used, in aggregate | {{e.g. privacy-respecting analytics; anonymised page views}} | **Only with your consent** — off by default; declining changes nothing about your access |
| **Advertising** {{if Advanced Ads / third-party ads are used}} | Show or measure ads | {{e.g. ad network cookies, conversion pixels}} | **Only with your consent** — off by default, and **never set on pseudonymous participant pages** |

## 5. Managing your consent and cookies
- **Browser controls.** You can view, block or delete cookies and clear local storage through your browser settings at any time. Clearing storage simply resets your saved preferences and UI state — your account is unaffected.
- **Consent banner.** Any consent banner we show **respects "decline non-essential"** as a first-class choice (declining is as easy as accepting) and remembers your decision. You can change it later via {{the "Cookie settings" / privacy link in the footer}}.
- **WordPress consent integration.** Where the **`wp_consent_api`** standard is available, our scripts register their consent category and **honour the visitor's stored consent state** before loading anything non-essential.

## 6. Third parties
Some pages may load services provided by third parties. We list them so you know who they are: {{e.g. **Google Maps** embeds for location display, {{ad network if any}}, {{embedded video provider}}}}. These providers may set their own cookies when their content loads; their use is governed by **their** privacy and cookie policies. We keep third-party embeds to what is needed for the feature to work, and we load them on a consent-aware basis where the category is non-essential. **Pseudonymous participant pages are never used for advertising or ad targeting**, and third-party ad/tracking scripts are not placed on them.

## 7. Changes and contact
We may update this policy as our tools or obligations change; the current version, its number and review date are shown at the top, and significant changes will be notified through the platform or by email where appropriate. Questions about cookies or consent:
- **Privacy Officer:** {{name / role}}
- **Email:** {{privacy@…}}
- **Phone:** {{phone}} ({{hours}})

---

### Plain-English summary (for participants)
*We keep tracking to a minimum. The only cookies that always run are the ones that keep you logged in and the site secure. Your settings — like bigger text, high contrast, your language, and your saved searches — are saved on your own device just to make things easier for you, not to follow you around. Anything optional, like analytics or ads, stays switched off unless you say yes, and ads are never shown on participant pages. You can clear all of this from your browser whenever you like.*

*Maintenance: keep this aligned with the Privacy Act 1988 (Cth) and the Privacy Policy; update the category table, the third-party list and the bracketed details to match the tools actually running (analytics, Advanced Ads, Google Maps, `wp_consent_api`) before publishing, and keep the "decline non-essential by default" and "no ads on participant pages" rules intact. Last updated: 2026-06-07.*
