# Design System — Shuffles Social Services Jobs and Engagements

**Version:** 1.1 (design) · **Date:** 2026-06-05

> **The rule:** every button, container, card, form control, badge, tab and modal in this plugin uses the shared tokens and component classes defined here. No one-off inline styles, no ad-hoc colours. One look and feel across admin and front-end, and across all four boards.

This system is deliberately small, token-driven, and **scoped** so it looks consistent on its own **and** sits politely on top of BuddyBoss / the active theme without a CSS arms race.

---

## 1. Scoping strategy (coexists with BuddyBoss + any theme)

- Every front-end surface is wrapped in a single root element: **`.sssj`** (e.g. `<div class="sssj sssj--board">…</div>`).
- **All** component selectors are prefixed `.sssj` (e.g. `.sssj .sssj-btn`). Nothing leaks to the host theme; the theme rarely reaches in.
- Design tokens are **CSS custom properties** declared on `.sssj`, so a reseller (or BuddyBoss theme) can override the whole palette by redefining a handful of variables — no forking the stylesheet.
- We avoid global element selectors (`button{}`, `a{}`) entirely. We never use `!important` for colour **except** in the accessibility modes (High-contrast / No-colour), where it must win — same discipline proven in Shuffles Provider Finder.
- **Accessibility-mode filters** (`filter`, `zoom`) are applied to inner **content blocks**, never to the `.sssj` root, so a `position:fixed` modal is never trapped (SPF lesson).

---

## 2. Design tokens

Declared on `.sssj` (front-end) and mirrored on `.sssj-admin` (wp-admin). These are the single source of truth.

```css
.sssj {
  /* Brand palette (shared with the Shuffles family) */
  --sssj-blue:        #3897e0;   /* primary brand */
  --sssj-blue-deep:   #1e5d9c;   /* hover / active / icons on light */
  --sssj-ink:         #0f172a;   /* near-black panels + headings */
  --sssj-text:        #1f2937;   /* body text */
  --sssj-muted:       #64748b;   /* secondary text */
  --sssj-line:        #e2e8f0;   /* borders / dividers */
  --sssj-bg:          #ffffff;   /* surface */
  --sssj-bg-soft:     #f8fafc;   /* sunken surface */

  /* Semantic */
  --sssj-success:     #15803d;
  --sssj-warning:     #b45309;
  --sssj-danger:      #b91c1c;
  --sssj-info:        #1e5d9c;

  /* Engagement-basis accents (used as a 4px left-edge on cards + basis chips) */
  --sssj-abn:         #7c3aed;   /* ABN / contractor work  (violet) */
  --sssj-tfn:         #0e7490;   /* TFN / employee work    (teal)   */
  --sssj-need:        #db2777;   /* participant need       (rose)   */

  /* Typography */
  --sssj-font:        inherit;            /* inherit the theme font by default */
  --sssj-fs-sm:       0.85rem;
  --sssj-fs:          1rem;
  --sssj-fs-lg:       1.15rem;
  --sssj-fs-h:        1.4rem;
  --sssj-lh:          1.5;
  --sssj-weight-strong: 600;

  /* Spacing scale (4px base) */
  --sssj-s1: 4px;  --sssj-s2: 8px;  --sssj-s3: 12px;
  --sssj-s4: 16px; --sssj-s5: 24px; --sssj-s6: 32px;

  /* Shape + depth */
  --sssj-radius:      10px;       /* cards, inputs, modals */
  --sssj-radius-pill: 999px;      /* chips, badges, pills */
  --sssj-shadow:      0 1px 3px rgba(15,23,42,.08), 0 4px 16px rgba(15,23,42,.06);
  --sssj-shadow-lift: 0 6px 24px rgba(15,23,42,.12);
  --sssj-ring:        0 0 0 3px rgba(56,151,224,.35);  /* focus ring */
  --sssj-dur:         .15s;       /* transition duration */
}
```

A **reseller / sector white-label** changes only `--sssj-blue` + `--sssj-blue-deep` (and optionally the basis accents) to re-skin the entire plugin.

---

## 3. Buttons — one component, clear variants

Class: `.sssj-btn` + a variant + an optional size. Buttons and links that act as buttons share the class, so they are visually identical.

| Variant | Class | Use |
|---|---|---|
| Primary | `.sssj-btn--primary` | the main action (Apply, Post, Search) — filled brand-blue, white text |
| Secondary | `.sssj-btn--secondary` | alternative action — white fill, brand-blue text + border |
| Ghost | `.sssj-btn--ghost` | low-emphasis (Cancel, Back) — transparent, muted text |
| Danger | `.sssj-btn--danger` | destructive (Withdraw, Delete) — danger colour |
| Icon | `.sssj-btn--icon` | square icon-only (voice, read-aloud) — paints SVG paths directly, never relies on `currentColor` (SPF lesson) |

Sizes: `.sssj-btn--sm` · default · `.sssj-btn--lg`. Full-width: `.sssj-btn--block`.

**Mandatory states for every button:** `:hover` (→ `--sssj-blue-deep`), `:focus-visible` (→ `--sssj-ring`, never removed), `:active`, `:disabled` (reduced opacity + `cursor:not-allowed`), and a busy state `.is-loading` (spinner using the site logo, as in SPF). Minimum hit target **44×44px**.

```css
.sssj .sssj-btn{
  display:inline-flex; align-items:center; gap:var(--sssj-s2);
  min-height:44px; padding:var(--sssj-s2) var(--sssj-s4);
  border-radius:var(--sssj-radius); border:2px solid transparent;
  font:var(--sssj-weight-strong) var(--sssj-fs)/1 var(--sssj-font);
  cursor:pointer; transition:background var(--sssj-dur), color var(--sssj-dur), border-color var(--sssj-dur);
}
.sssj .sssj-btn--primary{ background:var(--sssj-blue); color:#fff; }
.sssj .sssj-btn--primary:hover{ background:var(--sssj-blue-deep); }
.sssj .sssj-btn--secondary{ background:#fff; color:var(--sssj-blue-deep); border-color:var(--sssj-blue); }
.sssj .sssj-btn:focus-visible{ outline:none; box-shadow:var(--sssj-ring); }
.sssj .sssj-btn:disabled{ opacity:.55; cursor:not-allowed; }
```

---

## 4. Containers & cards

- **`.sssj-panel`** — the standard surface: white bg, `--sssj-radius`, `--sssj-line` border, `--sssj-shadow`, padding `--sssj-s5`.
- **`.sssj-card`** — a listing card. Carries a **4px left edge** in the engagement-basis accent (`--sssj-abn` / `--sssj-tfn` / `--sssj-need`) so a glance tells you the basis. Hover → `--sssj-shadow-lift`.
- **`.sssj-card--banned`** — red outline + strip (mirrors the SPF banned-card pattern) for ABN entries flagged against the banning register.
- **`.sssj-grid`** — responsive auto-fill grid (min 280px) for boards; single column under 600px.
- **`.sssj-stack`** / **`.sssj-row`** — vertical / horizontal fl-ex helpers using the spacing scale (no bespoke margins).

All containers use the same radius, border colour, shadow and padding scale — that is what makes the product feel like one thing.

---

## 5. Forms & filters

- **`.sssj-field`** wraps a label + control; **`.sssj-input`, `.sssj-select`, `.sssj-textarea`** share border, radius, focus ring and 44px min-height.
- The **main query input** gets the SPF "standout" treatment (dark `--sssj-ink` panel, light text, 2px brand border, larger placeholder) — scoped to the query field only, so it never fights High-contrast mode.
- **`.sssj-checkbox` / `.sssj-radio`** — custom-styled but keyboard-native; multi-selects (funding sources, credentials) use **`.sssj-chips`** (pill toggles).
- Validation: `.sssj-field--error` (danger border + helper text), `.sssj-field--ok`. Errors are announced via `aria-live`.

---

## 6. Badges, chips & status

- **`.sssj-badge`** — small pill. Semantic modifiers: `--verified` (success + ✓), `--pending`, `--abn`, `--tfn`, `--need`, `--telehealth`.
- **`.sssj-chip`** — interactive filter pill (toggles `.is-on`).
- The **"✓ Verified"** badge is only ever rendered server-side after admin sign-off (never from a client flag) — a visual rule that encodes a security rule.

---

## 7. Tabs, tables, modals

- **`.sssj-tabs`** — the board/section + settings tab bar. Admin settings tabs follow the INDEX-SYSTEM colour-dot convention (number-prefixed, domain-coloured).
- **`.sssj-table`** — zebra rows, sticky header, horizontal scroll on small screens.
- **`.sssj-modal`** — `position:fixed`, re-parented to `<body>` on open; backdrop; trap focus; `Esc` to close. Never place a CSS `filter` on an ancestor.

---

## 8. Accessibility & the CALD modes (must interoperate)

The design system is the substrate the CALD layer (plan §21) toggles:

- **High-contrast** (`.sssj-a11y-contrast`) — forces black/white with `!important`; our colour tokens deliberately don't use `!important` so this always wins.
- **No-colour** (`.sssj-a11y-mono`) — greyscale `filter` on **content blocks only**.
- **Larger-text / XL** — bumps the `--sssj-fs*` scale (or `zoom` on content blocks).
- **Easy-Read** — increased line-height, max line length, dyslexia-friendly spacing.
- All text meets **WCAG AA** contrast against its token background; focus is always visible (`--sssj-ring`); nothing conveys meaning by colour alone (basis is also labelled, not just accented).

---

## 9. Where the CSS lives

- `public/assets/css/sssj.css` — tokens + all front-end components (the file this document specifies).
- `admin/assets/css/sssj-admin.css` — admin reuse of the same tokens.
- One token block, imported once; components reference variables only. **No component hard-codes a hex value** — that is how "consistent look and feel" is enforced mechanically, not by discipline alone.

---

## 10. Definition of done (UI)

A surface is "on-system" when: it is wrapped in `.sssj`; every interactive element uses a `.sssj-*` component class; no inline colour/spacing literals; focus-visible works on every control; it passes AA contrast in default **and** High-contrast modes; and it looks unchanged whether BuddyBoss's theme is active or not.
