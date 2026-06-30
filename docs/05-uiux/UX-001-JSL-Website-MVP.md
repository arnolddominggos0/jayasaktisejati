# UI/UX Specification (UX)
## UX-001 — Jaya Sakti Line Website MVP
### Official UI/UX Design Specification

**Project:** Jaya Sakti App
**Document:** UX-001 — UI/UX Specification
**Version:** 1.1.0
**Status:** ✅ APPROVED — UI/UX Specification
**Document Owner:** UX Design Team
**Last Updated:** 2026-06-30
**Next Phase:** Sprint Planning (docs/06-sprint/)
**Sources of Truth (in precedence order):**
1. `docs/03-architecture/ADR-001.md` (v1.0.0, Accepted) — **Architectural Constitution; highest authority**
2. `docs/03-architecture/ARD-001-JSL-Website-MVP.md` (v1.0.0, Approved) — **Implementation Blueprint**
3. `docs/04-database/DBD-001-JSL-Website-MVP.md` (v1.1.0, Approved) — **Database Blueprint**
4. `docs/03-architecture/ARCHITECTURE_REVIEW.md` (v0.1.0, Accepted by ARB)
5. `docs/02-product/PRD-001-JSL-Website-MVP.md` (v1.1.0, Frozen — amended via approved Change Requests)
6. `docs/02-product/CR-001-001-Internal-Vessel-Certificate-Management.md` (v1.0.1, Approved)

> **Governance Rule**
> This document introduces **no new architecture decisions** and **no new database entities** of its own. It transforms the approved architecture and product requirements into a UI/UX specification. If any detail conflicts with an Accepted ADR, the **ADR always wins**. If any detail conflicts with the ARD, the **ARD wins**. If any detail conflicts with the DBD, the **DBD wins** over this document.
>
> **Prohibited in this document:** Application code, migrations, SQL statements, Eloquent models, Filament resource PHP definitions, and any executable code. This is **UI/UX design specification only** — wireframes, flows, design system, responsive rules, and accessibility requirements.

---

## Change History

| Version | Date | Author | Description |
|---------|------|--------|-------------|
| 1.0.0 | 2026-06-30 | UX Design Team | Initial UX-001 — UI/UX specification derived from PRD-001, ADR-001 through ADR-010, ARD-001, and DBD-001. |
| 1.1.0 | 2026-06-30 | UX Design Team | Added CMS **Certificates tab** wireframe (§12.6a) on the Vessel Listing Editor, per approved [CR-001-001](../02-product/CR-001-001-Internal-Vessel-Certificate-Management.md) and DBD-001 v1.1.0 `jsl_vessel_certificates` entity. Removed the superseded free-text "Certificates" field from the Sensitive section of the Vessel Listing Editor (§12.6). Updated public-wireframe "NEVER shown" notes (§6) to reference the certificate entity exclusion rather than a listing column. Updated CMS Page Map (§11.4) and UX Traceability Matrix (§16). |

---

## Table of Contents

1. Document Information
2. Design Principles
3. Design System
4. Public Website — Information Architecture
5. Public Website — User Flows
6. Public Website — Wireframes
7. Public Website — Responsive Specification
8. Public Website — Accessibility Specification
9. Public Website — SEO & Social Sharing Spec
10. Public Website — Analytics & Conversion Tracking Spec
11. CMS Panel — Layout & Navigation
12. CMS Panel — Wireframes
13. CMS Panel — Responsive Specification
14. Localization (i18n) UI Spec
15. UX Constraints & Rules
16. UX Traceability Matrix
17. Open UX Questions
18. Glossary

---

## 1. Document Information

| Field | Value |
|------|-------|
| Project | Jaya Sakti App |
| Module | Jaya Sakti Line Website MVP |
| Architecture Style | Modular Monolith |
| Framework | Laravel 11 |
| Language | PHP 8.3 |
| CMS Framework | Filament v3 |
| Database | MariaDB |
| Public Website Rendering | Server-side Blade (mobile-first, responsive) |
| CMS Rendering | Filament v3 (Filament UI components) |
| Predecessor Documents | PRD-001 (Frozen), Architecture Review (Accepted), ADR-001 (Accepted), ARD-001 (Approved), Architecture Walkthrough (Approved with Notes), DBD-001 (Approved) |
| Successor Document | Sprint Plan (docs/06-sprint/) |
| Authority | ADR > ARD > DBD > UX. This document must not contradict any Accepted ADR, the approved ARD, or the approved DBD. |

---

## 2. Design Principles

**References: PRD §1 (Vision), PRD §5 (Personas), PRD FR-11 (Mobile-first), ARD §17 (Quality Attributes), ADR-007 (Two surfaces)**

| # | Principle | Description | Source |
|---|-----------|-------------|--------|
| UX-1 | **Mobile-First** | Every page is designed for mobile first, then progressively enhanced for tablet and desktop. The primary persona (Andi) is mobile-first with low patience for slow sites. | PRD FR-11, Persona A |
| UX-2 | **Credibility & Trust** | The visual language conveys industrial credibility — clean, professional, maritime-inspired. Not flashy or consumer-oriented. | PRD BO-1, BO-2, Persona B |
| UX-3 | **Content-Led** | The design serves the content (company profile, services, vessel listings). Decoration does not compete with information. | PRD BO-3 |
| UX-4 | **Fast to Inquire** | The path from landing to inquiry is minimized — CTAs are prominent and persistent. The primary conversion is one click away from any vessel detail. | PRD BO-5, Persona A |
| UX-5 | **CMS Simplicity** | The CMS is designed for a non-technical Content Admin (Persona C). Filament-native patterns are used; no custom admin framework. Actions are clear, destructive actions are confirmed. | PRD Persona C, ADR-008 |
| UX-6 | **Bilingual-Ready** | The UI is structurally prepared for an ID/EN toggle (pending stakeholder approval). All text strings use Laravel i18n. Content fields have nullable EN counterparts in the database. | PRD FR-01 [RECOMMENDATION], AR-24, DBD §5 |
| UX-7 | **Sensitive-Data Safe** | Public wireframes show only public projection fields. No wireframe for any public page includes sensitive vessel data (real name, IMO, owner, vessel certificates, price). | ADR-005, PRD AC-4 |
| UX-8 | **Accessibility-First** | WCAG 2.1 AA basics are baked into the design system — contrast, alt text, keyboard navigation, focus states — not added retroactively. | PRD NFR (Accessibility) |
| UX-9 | **SEO-Friendly** | Clean URLs, semantic HTML, Open Graph tags, and structured content ensure search engine discoverability. | PRD FR-10 [RECOMMENDATION] |
| UX-10 | **Consistent with Ecosystem** | The CMS panel uses Filament v3 conventions consistent with the existing admin/fc/customer panels. The brand identity aligns with the existing Jaya Sakti brand (#0137A1 primary). | ADR-003, ADR-008, existing panel config |

---

## 3. Design System

**References: PRD FR-11 (Responsive), PRD NFR (Accessibility), ADR-007 (Two surfaces), ADR-008 (Filament CMS)**

### 3.1 Color Palette

#### 3.1.1 Public Website

| Role | Name | Hex | Usage | Contrast (on white) |
|------|------|-----|-------|---------------------|
| Primary | JSL Navy Blue | `#0137A1` | Headers, primary CTAs, navigation active state, links | 7.6:1 ✅ AA |
| Primary Dark | Deep Navy | `#002A7F` | Hover states, active buttons | — |
| Primary Light | Light Blue | `#E8F0FE` | Section backgrounds, badge backgrounds | — |
| Accent | Maritime Teal | `#0D9488` | Secondary CTAs, status badges (Open), accents | 4.5:1 ✅ AA |
| Accent Light | Light Teal | `#CCFBF1` | Open status badge background | — |
| Warning | Amber | `#D97706` | Closed status badge, warning states | 4.6:1 ✅ AA |
| Warning Light | Light Amber | `#FEF3C7` | Closed status badge background | — |
| Neutral Dark | Charcoal | `#1F2937` | Body text, headings | 14.7:1 ✅ AAA |
| Neutral Medium | Slate | `#6B7280` | Secondary text, captions, meta info | 4.7:1 ✅ AA |
| Neutral Light | Light Gray | `#F3F4F6` | Section backgrounds, card backgrounds | — |
| Neutral Border | Border Gray | `#E5E7EB` | Card borders, dividers, input borders | — |
| White | White | `#FFFFFF` | Page background, card background | — |
| Success | Green | `#16A34A` | Form success messages, confirmation states | 4.5:1 ✅ AA |
| Error | Red | `#DC2626` | Form validation errors, destructive actions | 5.1:1 ✅ AA |

> **Brand alignment:** The primary color `#0137A1` matches the existing Jaya Sakti Sejati brand color used in the operational `admin` and `fc` Filament panels. This ensures brand consistency across the ecosystem.

#### 3.1.2 CMS Panel (Filament)

| Role | Hex | Source |
|------|-----|--------|
| Primary | `#0137A1` | Consistent with existing admin panel |
| Success | Filament Green | Filament default |
| Warning | Filament Amber | Filament default |
| Danger | Filament Red | Filament default |

> The CMS panel uses Filament v3's built-in color system with the primary color overridden to `#0137A1` for brand consistency. No custom color palette is designed for the CMS — Filament defaults are used for all non-primary colors to maintain ecosystem consistency and reduce maintenance.

### 3.2 Typography

#### 3.2.1 Public Website

| Element | Font Family | Weight | Size (Mobile) | Size (Desktop) | Line Height | Color |
|---------|-------------|--------|---------------|----------------|-------------|-------|
| H1 (Page Title) | System UI sans-serif | 700 | 28px (1.75rem) | 40px (2.5rem) | 1.2 | Charcoal |
| H2 (Section Title) | System UI sans-serif | 600 | 24px (1.5rem) | 32px (2rem) | 1.25 | Charcoal |
| H3 (Card Title) | System UI sans-serif | 600 | 20px (1.25rem) | 24px (1.5rem) | 1.3 | Charcoal |
| H4 (Sub-section) | System UI sans-serif | 600 | 18px (1.125rem) | 20px (1.25rem) | 1.35 | Charcoal |
| Body | System UI sans-serif | 400 | 16px (1rem) | 16px (1rem) | 1.6 | Charcoal |
| Body Small | System UI sans-serif | 400 | 14px (0.875rem) | 14px (0.875rem) | 1.5 | Slate |
| Caption / Meta | System UI sans-serif | 400 | 12px (0.75rem) | 12px (0.75rem) | 1.4 | Slate |
| Rich Text Content | System UI sans-serif | 400 | 16px (1rem) | 16px (1rem) | 1.7 | Charcoal |
| Button Label | System UI sans-serif | 600 | 14px (0.875rem) | 14px (0.875rem) | 1.2 | White (on primary) |
| Nav Link | System UI sans-serif | 500 | 14px (0.875rem) | 15px (0.9375rem) | 1.3 | Charcoal / Navy Blue (active) |

> **Font stack:** `'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif`. Inter is loaded via CDN/Google Fonts with `font-display: swap` to avoid render-blocking. System UI fallback ensures fast initial render.

> **Rich text:** Content managed via CMS (company profile, services, vessel marketing description) uses the same body typography. The Filament RichEditor outputs HTML that is styled by the public website's rich-text stylesheet (headings, lists, bold, links, images).

#### 3.2.2 CMS Panel (Filament)

Filament v3 uses its own default typography (Inter font, Filament's built-in type scale). No custom typography is designed for the CMS.

### 3.3 Spacing System

| Token | Value | Usage |
|-------|-------|-------|
| `xs` | 4px (0.25rem) | Tight spacing within components (icon-to-label) |
| `sm` | 8px (0.5rem) | Small gaps between related elements |
| `md` | 16px (1rem) | Default spacing between elements, card padding |
| `lg` | 24px (1.5rem) | Section internal padding, card-to-card gap |
| `xl` | 32px (2rem) | Section padding (mobile), major section gap |
| `2xl` | 48px (3rem) | Section padding (desktop), large section gap |
| `3xl` | 64px (4rem) | Hero section padding (desktop) |

### 3.4 Breakpoints (Responsive)

| Breakpoint | Min Width | Target Devices | Source |
|------------|-----------|----------------|--------|
| `xs` (base) | 0px | Mobile portrait (320px+) | PRD FR-11 (mobile-first) |
| `sm` | 640px | Mobile landscape, small tablet | — |
| `md` | 768px | Tablet (portrait) | — |
| `lg` | 1024px | Tablet (landscape), small desktop | — |
| `xl` | 1280px | Desktop | — |
| `2xl` | 1536px | Large desktop | — |

> **Convention:** Styles are written mobile-first (base = mobile), then enhanced with `min-width` media queries. This aligns with PRD FR-11 (mobile-first) and Tailwind CSS conventions (which Filament already uses).

### 3.5 Iconography

| Usage | Icon Set | Source |
|-------|----------|--------|
| Public website | Heroicons (Outline, 24px) | MIT license, consistent with Filament/Blade UI Kit |
| CMS panel | Filament built-in icons (Heroicons) | Filament v3 default |

> Icons are decorative only when accompanying text labels. Standalone icons must have `aria-label` or visually-hidden text for accessibility (UX-8, WCAG 2.1 AA).

### 3.6 Component Primitives (Public Website)

| Component | Variants | States | Usage |
|-----------|----------|--------|-------|
| **Button** | Primary (Navy Blue), Secondary (outline Navy Blue), Ghost (text only), WhatsApp (green #25D366) | Default, Hover, Focus, Disabled | CTAs, form submit, inquiry channels |
| **Card** | Default (white bg, border), Featured (light blue bg), Image Card (image top, content bottom) | Default, Hover (shadow) | Vessel listing cards, service cards, gallery items |
| **Badge** | Open (teal), Closed (amber), Info (light blue) | Default | Vessel status indicators |
| **Navigation Bar** | Sticky top, mobile (hamburger menu), desktop (horizontal links) | Default, Scrolled (shadow) | Global header |
| **Footer** | Multi-column (desktop), stacked (mobile) | Default | Global footer |
| **Form Input** | Text, Textarea, Select, Checkbox | Default, Focus, Error, Disabled | Inquiry form |
| **Image Gallery** | Grid (thumbnails), Lightbox (full-size) | Default, Active | Vessel detail images |
| **Section** | Default (white bg), Alternate (light gray bg) | Default | Page sectioning |
| **Breadcrumbs** | Text links separated by `/` | Default, Current (non-link) | Vessel detail, About sub-pages |
| **Pagination** | Numbered (desktop), Prev/Next (mobile) | Default, Active | Vessel listing index, gallery |

### 3.7 Component Primitives (CMS Panel)

The CMS uses Filament v3's built-in component library. No custom components are designed. The following Filament components are used:

| Filament Component | Usage |
|---------------------|-------|
| Filament Table | List views (vessel listings, services, gallery, inquiries) |
| Filament Form | Create/edit forms (all entities) |
| Filament FileUpload | Image upload (vessel images, gallery, service icons) |
| Filament RichEditor | Rich text (company profile, service description, vessel marketing description) |
| Filament Toggle | Boolean switches (is_visible, status toggle) |
| Filament Select / CheckboxList | Enum selection (vessel_type, status) |
| Filament Repeater | Ordered image management (vessel images with sort_order) |
| Filament Section | Form grouping (public fields vs. sensitive fields) |
| Filament Grid | Form layout (2-column on desktop) |

---

## 4. Public Website — Information Architecture

**References: PRD §10 (Sitemap), ARD §12 (Routing Architecture), DBD §3 (Traceability)**

### 4.1 Sitemap (Public)

```
JSL Website (Public)
│
├── Home                         GET /                        jsl.public.home
├── About
│   ├── Company Overview         GET /about                   jsl.public.about
│   └── Vision & Mission         GET /about#vision-mission    (anchor on About)
├── Services                     GET /services                jsl.public.services
├── Vessel Trading
│   ├── Listing Index            GET /vessels                 jsl.public.vessels.index
│   └── Vessel Detail            GET /vessels/{ref}           jsl.public.vessels.show
├── Gallery                      GET /gallery                 jsl.public.gallery
├── Contact                      GET /contact                 jsl.public.contact
│
└── (Form submission)
    └── Inquiry                  POST /inquiries              jsl.public.inquiries.store
```

### 4.2 Navigation Structure

#### 4.2.1 Primary Navigation (Header)

| Label (ID) | Link | Visible On |
|------------|------|------------|
| Beranda (Home) | `/` | All pages |
| Tentang (About) | `/about` | All pages |
| Layanan (Services) | `/services` | All pages |
| Perdagangan Kapal (Vessel Trading) | `/vessels` | All pages |
| Galeri (Gallery) | `/gallery` | All pages |
| Kontak (Contact) | `/contact` | All pages |
| [ID/EN Toggle] | (toggle) | All pages (if EN approved) |

> **Mobile:** Navigation collapses to a hamburger menu. The menu opens as a full-screen overlay with vertically stacked links.
> **Desktop:** Navigation is a horizontal bar in the header, right-aligned. Logo is left-aligned.
> **Language:** All navigation labels use Laravel i18n strings (ID default). Labels shown above are Indonesian (default per PRD).

#### 4.2.2 Footer Navigation

| Section | Content | Source |
|---------|---------|--------|
| Brand | Site name, tagline, brief description | `jsl_site_settings` |
| Quick Links | Home, About, Services, Vessel Trading, Gallery, Contact | Static |
| Contact | Phone (display), Email (display), Address | `jsl_site_settings` |
| Social | Facebook, Instagram, LinkedIn (if configured) | `jsl_site_settings` |
| Inquiry CTAs | WhatsApp button, Email button | `.env` broker contact |
| Copyright | © {year} Jaya Sakti Line. All rights reserved. | Static |

---

## 5. Public Website — User Flows

**References: PRD §11 (Public User Flow), PRD §12 (Admin Flow), ARD §9 (Request Flow)**

### 5.1 Flow A — Browse & Inquire (Most Common)

**Persona:** Andi (Industrial Logistics Manager, mobile-first)
**Goal:** Find a vessel and inquire within 10 minutes.

```
┌──────────┐     ┌──────────┐     ┌──────────────┐     ┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│  Home     │────►│ Vessel   │────►│ Vessel Detail │────►│ Click CTA    │────►│ Confirmation │────►│  Done        │
│           │     │ Trading  │     │              │     │ (WA/Email/   │     │              │     │              │
│ See hero, │     │ Index    │     │ See general  │     │  Form)       │     │ "Thank you"  │     │              │
│ featured  │     │          │     │ info, images,│     │              │     │ message      │     │              │
│ vessels   │     │ Filter   │     │ status badge │     │              │     │              │     │              │
│           │     │ by type  │     │              │     │              │     │              │     │              │
└──────────┘     └──────────┘     └──────────────┘     └──────────────┘     └──────────────┘     └──────────────┘
```

**Steps:**
1. Land on Home — see hero banner, brief company intro, 3 featured vessels (first 3 Open listings).
2. Click "Perdagangan Kapal" in nav → Vessel Trading Index.
3. (Optional) Filter by vessel type or status.
4. Click a vessel card → Vessel Detail page.
5. See: general info, up to 6 images, status badge (Open/Closed), 3 inquiry CTAs.
6. Click one of: WhatsApp, Email, or Inquiry Form.
   - **WhatsApp:** Opens `wa.me` with prefilled message → leaves site.
   - **Email:** Opens mail client with prefilled subject → leaves site.
   - **Form:** Opens form (inline or modal) → fill name, contact, message, consent → submit.
7. See confirmation message: "Inquiry submitted. We will contact you soon."
8. GA4 conversion event fires (all three channels).

### 5.2 Flow B — Brand Credibility Check

**Persona:** Pak Hendra (Vessel Owner)
**Goal:** Assess JSL credibility and propose a listing.

```
┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────────┐     ┌──────────────┐
│  Home     │────►│ About    │────►│ Services │────►│ Contact      │────►│  Done        │
│           │     │          │     │          │     │ (general)    │     │              │
│           │     │ Read     │     │ Under-   │     │ Send general │     │              │
│           │     │ company  │     │ stand    │     │ inquiry      │     │              │
│           │     │ overview,│     │ capabili-│     │              │     │              │
│           │     │ vision,  │     │ ties     │     │              │     │              │
│           │     │ mission  │     │          │     │              │     │              │
└──────────┘     └──────────┘     └──────────┘     └──────────────┘     └──────────────┘
```

### 5.3 Flow C — Gallery Browse

```
┌──────────┐     ┌──────────┐     ┌──────────────┐
│  Home     │────►│ Gallery  │────►│  Done        │
│  (or nav) │     │          │     │              │
│           │     │ Browse   │     │              │
│           │     │ images,  │     │              │
│           │     │ filter   │     │              │
│           │     │ by cate- │     │              │
│           │     │ gory     │     │              │
└──────────┘     └──────────┘     └──────────────┘
```

### 5.4 Flow D — Direct Vessel Detail (from Search Engine / Shared Link)

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│  Search      │────►│ Vessel Detail │────►│  CTA → Done  │
│  Engine /    │     │              │     │              │
│  Shared Link │     │              │     │              │
│  /vessels/   │     │              │     │              │
│  JSL-001     │     │              │     │              │
└──────────────┘     └──────────────┘     └──────────────┘
```

> This flow is why SEO-friendly URLs and Open Graph tags matter (PRD FR-10). The vessel detail page must be fully crawlable and shareable.

---

## 6. Public Website — Wireframes

**References: PRD §10 (Sitemap), PRD §15 (Vessel Trading Requirements), PRD §16 (Inquiry Requirements), ADR-005 (Public Projection — only public fields shown), ADR-009 (Responsive images), ARD §6 (Component Diagram), ARD §12 (Routing), DBD §13 (Physical Data Model)**

> **Wireframe convention:** These are low-fidelity structural wireframes showing layout, content hierarchy, and component placement. They do not prescribe pixel-perfect visual design. All wireframes are mobile-first; desktop variants are shown where the layout differs significantly.
>
> **ADR-005 constraint:** All public wireframes show ONLY public projection fields. Sensitive fields (real_vessel_name, imo_number, owner_details, price_commercial_terms) are NEVER shown in any public wireframe. The entire `jsl_vessel_certificates` entity (CR-001-001, DBD §5.2.3, §15.2a) is likewise excluded from every public-facing query and view.

### 6.1 Global Header & Footer

#### 6.1.1 Header — Mobile

```
┌─────────────────────────────────┐
│  [☰]    JSL Logo    [ID/EN]     │  ← Sticky header, 56px height
└─────────────────────────────────┘

(When hamburger [☰] tapped:)
┌─────────────────────────────────┐
│  [✕]    JSL Logo                │
│─────────────────────────────────│
│                                 │
│  Beranda                        │
│  Tentang                        │
│  Layanan                        │
│  Perdagangan Kapal              │
│  Galeri                         │
│  Kontak                         │
│                                 │
│  [WhatsApp] [Email]             │
│                                 │
└─────────────────────────────────┘
```

#### 6.1.2 Header — Desktop

```
┌─────────────────────────────────────────────────────────────────────────┐
│  JSL Logo    Beranda  Tentang  Layanan  Perdagangan Kapal  Galeri  Kontak  [ID/EN]  │
└─────────────────────────────────────────────────────────────────────────┘
```

#### 6.1.3 Footer — Mobile

```
┌─────────────────────────────────┐
│  JSL Logo                        │
│  Tagline / brief description     │
│                                 │
│  Quick Links:                    │
│  • Beranda                       │
│  • Tentang                       │
│  • Layanan                       │
│  • Perdagangan Kapal             │
│  • Galeri                        │
│  • Kontak                        │
│                                 │
│  Kontak:                         │
│  📞 +62 xxx (display)            │
│  ✉ info@jayasakti...             │
│  📍 Address                      │
│                                 │
│  [Facebook] [Instagram] [LinkedIn]│
│                                 │
│  [WhatsApp CTA] [Email CTA]      │
│                                 │
│  © 2026 Jaya Sakti Line          │
└─────────────────────────────────┘
```

#### 6.1.4 Footer — Desktop

```
┌─────────────────────────────────────────────────────────────────────────┐
│  JSL Logo              │ Quick Links       │ Kontak           │ Social    │
│  Tagline / brief       │ Beranda           │ 📞 +62 xxx       │ [FB]      │
│  description           │ Tentang           │ ✉ info@jsl...    │ [IG]      │
│                        │ Layanan           │ 📍 Address       │ [LinkedIn]│
│                        │ Perdagangan Kapal │                  │           │
│                        │ Galeri            │ [WhatsApp] [Email]│           │
│                        │ Kontak            │                  │           │
│──────────────────────────────────────────────────────────────────────────│
│  © 2026 Jaya Sakti Line. All rights reserved.                            │
└─────────────────────────────────────────────────────────────────────────┘
```

### 6.2 Home Page

**Route:** `GET /` → `HomeController@index`
**Data sources:** `jsl_site_settings` (brand), `jsl_company_profiles` (brief intro), `jsl_vessel_listings` (featured = first 3 Open listings), `jsl_services` (visible services)
**PRD reference:** FR-01, FR-02, FR-03, FR-11
**ARD reference:** ARD §6 Company Component, Trading Component, Services Component

#### 6.2.1 Home — Mobile

```
┌─────────────────────────────────┐
│  [Header — see §6.1.1]          │
├─────────────────────────────────┤
│                                 │
│  HERO SECTION                   │
│  ┌─────────────────────────┐   │
│  │  Background image        │   │
│  │  (maritime/vessel)       │   │
│  │                          │   │
│  │  Jaya Sakti Line         │   │
│  │  Vessel Trading &        │   │
│  │  Shipping Services       │   │
│  │                          │   │
│  │  [Lihat Kapal]  [Kontak] │   │
│  └─────────────────────────┘   │
│                                 │
├─────────────────────────────────┤
│  ABOUT TEASER SECTION           │
│  (light gray bg)                │
│                                 │
│  Tentang Kami                   │
│  Brief company description...   │
│  (from jsl_company_profiles)    │
│                                 │
│  [Selengkapnya →]               │
│                                 │
├─────────────────────────────────┤
│  SERVICES SECTION               │
│                                 │
│  Layanan Kami                   │
│                                 │
│  ┌─────────┐  ┌─────────┐      │
│  │ [Icon]  │  │ [Icon]  │      │
│  │ Title 1 │  │ Title 2 │      │
│  │ Desc... │  │ Desc... │      │
│  └─────────┘  └─────────┘      │
│  ┌─────────┐                   │
│  │ [Icon]  │                   │
│  │ Title 3 │                   │
│  │ Desc... │                   │
│  └─────────┘                   │
│                                 │
│  [Selengkapnya →]               │
│                                 │
├─────────────────────────────────┤
│  FEATURED VESSELS SECTION       │
│  (light gray bg)                │
│                                 │
│  Peluang Kapal                  │
│                                 │
│  ┌─────────────────────────┐   │
│  │  [Thumbnail image]       │   │
│  │  JSL-001                 │   │
│  │  Bulk Carrier            │   │
│  │  [Open] badge            │   │
│  │  Year: 2015  GT: 25000   │   │
│  └─────────────────────────┘   │
│  ┌─────────────────────────┐   │
│  │  [Thumbnail image]       │   │
│  │  JSL-002                 │   │
│  │  Tugboat                 │   │
│  │  [Open] badge            │   │
│  │  Year: 2018  GT: 500     │   │
│  └─────────────────────────┘   │
│  ┌─────────────────────────┐   │
│  │  [Thumbnail image]       │   │
│  │  JSL-003                 │   │
│  │  Barge                   │   │
│  │  [Open] badge            │   │
│  │  Year: 2020  DWT: 8000   │   │
│  └─────────────────────────┘   │
│                                 │
│  [Lihat Semua Kapal →]          │
│                                 │
├─────────────────────────────────┤
│  CTA SECTION                    │
│  (Navy Blue bg)                 │
│                                 │
│  Tertarik? Hubungi Kami         │
│  [WhatsApp] [Email] [Form]      │
│                                 │
├─────────────────────────────────┤
│  [Footer — see §6.1.3]          │
└─────────────────────────────────┘
```

#### 6.2.2 Home — Desktop Differences

- Hero section is full-width with overlay text centered or left-aligned.
- Services section: 3-4 cards in a horizontal row.
- Featured vessels: 3 cards in a horizontal row.
- CTA section: centered content with 3 inline buttons.

### 6.3 About Page (Company Overview + Vision & Mission)

**Route:** `GET /about` → `AboutController@index`
**Data sources:** `jsl_company_profiles` (about, overview, vision, mission)
**PRD reference:** FR-01, §14.1
**ARD reference:** ARD §6 Company Component

```
┌─────────────────────────────────┐
│  [Header]                        │
├─────────────────────────────────┤
│                                 │
│  PAGE HEADER                    │
│  Tentang Jaya Sakti Line        │
│  (H1)                           │
│                                 │
├─────────────────────────────────┤
│  ABOUT SECTION                  │
│                                 │
│  [Optional hero image]          │
│                                 │
│  (Rich text from about field)   │
│  Full company about content...  │
│                                 │
├─────────────────────────────────┤
│  OVERVIEW SECTION               │
│  (light gray bg)                │
│                                 │
│  Company Overview               │
│  (H2)                           │
│                                 │
│  (Rich text from overview)      │
│  Detailed overview content...   │
│                                 │
├─────────────────────────────────┤
│  VISION & MISSION SECTION       │
│                                 │
│  ┌───────────┐ ┌───────────┐   │
│  │ VISION    │ │ MISSION   │   │
│  │ (H3)      │ │ (H3)      │   │
│  │           │ │           │   │
│  │ (Rich     │ │ (Rich     │   │
│  │  text)    │ │  text)    │   │
│  └───────────┘ └───────────┘   │
│  (Side-by-side on desktop,      │
│   stacked on mobile)            │
│                                 │
├─────────────────────────────────┤
│  CTA SECTION                    │
│  (Navy Blue bg)                 │
│                                 │
│  Pelajari layanan kami          │
│  [Lihat Layanan →]              │
│                                 │
├─────────────────────────────────┤
│  [Footer]                        │
└─────────────────────────────────┘
```

### 6.4 Services Page

**Route:** `GET /services` → `ServiceController@index`
**Data sources:** `jsl_services` (where `is_visible = true`, ordered by `sort_order`)
**PRD reference:** FR-02, §14.2
**ARD reference:** ARD §6 Services Component

```
┌─────────────────────────────────┐
│  [Header]                        │
├─────────────────────────────────┤
│                                 │
│  PAGE HEADER                    │
│  Layanan Kami                   │
│  (H1)                           │
│  Brief intro text               │
│                                 │
├─────────────────────────────────┤
│  SERVICES GRID                  │
│                                 │
│  ┌─────────────────────────┐   │
│  │  [Icon/Image]            │   │
│  │  Service Title 1         │   │
│  │  (H3)                    │   │
│  │  Description text...     │   │
│  └─────────────────────────┘   │
│  ┌─────────────────────────┐   │
│  │  [Icon/Image]            │   │
│  │  Service Title 2         │   │
│  │  (H3)                    │   │
│  │  Description text...     │   │
│  └─────────────────────────┘   │
│  ...                            │
│                                 │
│  (1 column on mobile,           │
│   2-3 columns on desktop)       │
│                                 │
├─────────────────────────────────┤
│  CTA SECTION                    │
│  Hubungi kami untuk konsultasi  │
│  [Kontak →]                     │
│                                 │
├─────────────────────────────────┤
│  [Footer]                        │
└─────────────────────────────────┘
```

### 6.5 Vessel Trading Index

**Route:** `GET /vessels` → `VesselListingController@index`
**Data sources:** `jsl_vessel_listings` (where `deleted_at IS NULL`, ordered by `created_at DESC`), projection-only fields
**PRD reference:** FR-03, AC-2, §15.1
**ARD reference:** ARD §6 Trading Component, ADR-005 (Projection)
**DBD reference:** DBD §13.4 (`jsl_vessel_listings`), DBD §15.2 (Field Classification)

> **ADR-005 CRITICAL:** This wireframe shows ONLY public projection fields. The following fields are NEVER displayed: `real_vessel_name`, `imo_number`, `owner_details`, `price_commercial_terms`. The `jsl_vessel_certificates` entity (CR-001-001) is never displayed or eager-loaded on this page.

#### 6.5.1 Vessel Trading Index — Mobile

```
┌─────────────────────────────────┐
│  [Header]                        │
├─────────────────────────────────┤
│                                 │
│  PAGE HEADER                    │
│  Perdagangan Kapal              │
│  (H1)                           │
│  Temukan peluang kapal...       │
│                                 │
├─────────────────────────────────┤
│  FILTER BAR                     │
│  (light gray bg, sticky)        │
│                                 │
│  [Tipe Kapal ▼]  [Status ▼]    │
│  (Dropdowns: vessel_type,       │
│   status: All/Open/Closed)      │
│                                 │
├─────────────────────────────────┤
│  VESSEL CARDS                   │
│                                 │
│  ┌─────────────────────────┐   │
│  │  [Thumbnail image]       │   │
│  │  (first image, variant_  │   │
│  │   thumbnail, lazy-loaded)│   │
│  │                          │   │
│  │  JSL-001     [Open]      │   │
│  │  Bulk Carrier            │   │
│  │  ──────────────────────  │   │
│  │  Year Built: 2015        │   │
│  │  Gross Tonnage: 25,000   │   │
│  │  Deadweight: 35,000      │   │
│  │  Trading Area: Indonesia │   │
│  │                          │   │
│  │  [Lihat Detail →]        │   │
│  └─────────────────────────┘   │
│                                 │
│  ┌─────────────────────────┐   │
│  │  [Thumbnail image]       │   │
│  │  JSL-002     [Open]      │   │
│  │  Tugboat                 │   │
│  │  ──────────────────────  │   │
│  │  Year Built: 2018        │   │
│  │  Gross Tonnage: 500      │   │
│  │  [Lihat Detail →]        │   │
│  └─────────────────────────┘   │
│                                 │
│  ┌─────────────────────────┐   │
│  │  [Thumbnail image]       │   │
│  │  JSL-003     [Closed]    │   │
│  │  Barge                   │   │
│  │  ──────────────────────  │   │
│  │  Year Built: 2020        │   │
│  │  [Lihat Detail →]        │   │
│  └─────────────────────────┘   │
│                                 │
│  [← Prev]  1  2  3  [Next →]   │
│  (Pagination, 12 per page)      │
│                                 │
├─────────────────────────────────┤
│  [Footer]                        │
└─────────────────────────────────┘
```

#### 6.5.2 Vessel Trading Index — Desktop Differences

- Filter bar is horizontal with inline dropdowns and a "Filter" button.
- Vessel cards: 3 cards per row (grid layout).
- Pagination: numbered with ellipsis for many pages.

#### 6.5.3 Vessel Card — Detail

Each vessel card displays the following **public projection** fields only:

| Card Element | Data Field | DBD Classification | Notes |
|-------------|-----------|-------------------|-------|
| Thumbnail image | `jsl_vessel_images` (first by sort_order) → `jsl_media_assets` (variant_thumbnail_path) | Public | Responsive srcset, lazy-loaded |
| Reference code | `public_ref_code` | Public | e.g., "JSL-001" — NOT the real vessel name |
| Status badge | `status` | Public | "Open" (teal) or "Closed" (amber) |
| Vessel type | `vessel_type` | Public | e.g., "Bulk Carrier" |
| Year built | `year_built` | Public | If null, not shown |
| Gross tonnage | `gross_tonnage` | Public | If null, not shown |
| Deadweight | `deadweight` | Public | If null, not shown |
| Trading area | `trading_area` | Public | If null, not shown |
| "Lihat Detail" link | Route to detail page | — | Links to `/vessels/{public_ref_code}` |

> **NEVER shown on card:** `real_vessel_name`, `imo_number`, `owner_details`, `price_commercial_terms`, nor any data from `jsl_vessel_certificates` (ADR-005, AC-4, CR-001-001).

### 6.6 Vessel Detail Page

**Route:** `GET /vessels/{ref}` → `VesselListingController@show`
**Data sources:** `jsl_vessel_listings` (by `public_ref_code`), `jsl_vessel_images` (ordered), `jsl_media_assets` (image variants)
**PRD reference:** FR-03, FR-04, AC-3, AC-4, AC-5, AC-6, §15.1, §16.1
**ARD reference:** ARD §6 Trading Component, Inquiry Component, ADR-005 (Projection)
**DBD reference:** DBD §13.4, §13.5, §15.2

> **ADR-005 CRITICAL:** This wireframe shows ONLY public projection fields. The inquiry CTAs are the primary conversion mechanism.

```
┌─────────────────────────────────┐
│  [Header]                        │
├─────────────────────────────────┤
│                                 │
│  BREADCRUMBS                    │
│  Beranda / Perdagangan Kapal /  │
│  JSL-001                        │
│                                 │
├─────────────────────────────────┤
│  VESSEL HEADER                  │
│                                 │
│  JSL-001          [Open] badge  │
│  Bulk Carrier                   │
│                                 │
├─────────────────────────────────┤
│  IMAGE GALLERY                  │
│                                 │
│  ┌─────────────────────────┐   │
│  │                         │   │
│  │  [Main image]            │   │
│  │  (variant_medium or      │   │
│  │   variant_large)         │   │
│  │                         │   │
│  └─────────────────────────┘   │
│  ┌──┐ ┌──┐ ┌──┐ ┌──┐ ┌──┐    │
│  │  │ │  │ │  │ │  │ │  │    │
│  └──┘ └──┘ └──┘ └──┘ └──┘    │
│  (Thumbnail strip, up to 6,    │
│   click to change main image)  │
│                                 │
├─────────────────────────────────┤
│  GENERAL INFORMATION            │
│                                 │
│  Spesifikasi Kapal              │
│  (H2)                           │
│                                 │
│  ┌─────────────┬─────────────┐ │
│  │ Tipe Kapal   │ Bulk Carrier│ │
│  │ Tahun Buat   │ 2015        │ │
│  │ Gross Tonnage│ 25,000 GT   │ │
│  │ Deadweight   │ 35,000 DWT  │ │
│  │ LOA          │ 180 m       │ │
│  │ Beam         │ 32 m        │ │
│  │ Draft        │ 11 m        │ │
│  │ Engine Power │ 8,000 kW    │ │
│  │ Flag Registry│ Indonesia   │ │
│  │ Trading Area │ Southeast   │ │
│  │              │ Asia        │ │
│  └─────────────┴─────────────┘ │
│  (Key-value pairs, 2-col on    │
│   desktop, 1-col on mobile)    │
│                                 │
├─────────────────────────────────┤
│  MARKETING DESCRIPTION          │
│  (light gray bg)                │
│                                 │
│  Deskripsi                      │
│  (H2)                           │
│                                 │
│  (Rich text from                │
│   marketing_description)        │
│  Detailed marketing copy...     │
│                                 │
├─────────────────────────────────┤
│  INQUIRY CTA SECTION            │
│  (Navy Blue bg)                 │
│                                 │
│  Tertarik dengan kapal ini?    │
│  Hubungi broker kami:           │
│                                 │
│  ┌──────────┐ ┌──────────┐    │
│  │ [WhatsApp]│ │  [Email] │    │
│  │  Green    │ │  Outline │    │
│  │  Button   │ │  Button  │    │
│  └──────────┘ └──────────┘    │
│  ┌──────────────────────┐     │
│  │  [Kirim Inquiry Form] │     │
│  │   Primary Button      │     │
│  └──────────────────────┘     │
│                                 │
│  (If status = Closed:)          │
│  "Kapal ini berstatus Closed.  │
│   Inquiry tidak tersedia."     │
│  (CTAs are disabled/hidden)     │
│                                 │
├─────────────────────────────────┤
│  INQUIRY FORM (expandable)      │
│  (Shown when "Kirim Inquiry     │
│   Form" is clicked)             │
│                                 │
│  ┌─────────────────────────┐   │
│  │  Form Inquiry            │   │
│  │                          │   │
│  │  Nama *                  │   │
│  │  [________________]      │   │
│  │                          │   │
│  │  Perusahaan              │   │
│  │  [________________]      │   │
│  │                          │   │
│  │  Email                   │   │
│  │  [________________]      │   │
│  │                          │   │
│  │  Phone                   │   │
│  │  [________________]      │   │
│  │  (Email or Phone req.)   │   │
│  │                          │   │
│  │  Pesan *                 │   │
│  │  [________________]      │   │
│  │  [________________]      │   │
│  │  [________________]      │   │
│  │                          │   │
│  │  ☐ Saya menyetujui       │   │
│  │    penggunaan data... *  │   │
│  │  (Consent checkbox)      │   │
│  │                          │   │
│  │  Vessel: JSL-001         │   │
│  │  (hidden, auto-attached) │   │
│  │                          │   │
│  │  [Kirim]                 │   │
│  └─────────────────────────┘   │
│                                 │
├─────────────────────────────────┤
│  [Footer]                        │
└─────────────────────────────────┘
```

#### 6.6.1 Vessel Detail — Field Display Rules

| Section | Data Field | Display Rule | If Null |
|---------|-----------|--------------|---------|
| Header | `public_ref_code` | Always shown | — |
| Header | `status` | Badge: Open (teal) / Closed (amber) | — |
| Header | `vessel_type` | Always shown | — |
| Gallery | `jsl_vessel_images` (0-6) | Main image + thumbnail strip | If 0 images: show placeholder |
| Specs | `year_built` | Key-value row | Row hidden |
| Specs | `flag_registry` | Key-value row | Row hidden |
| Specs | `gross_tonnage` | Key-value row, formatted with thousands separator | Row hidden |
| Specs | `deadweight` | Key-value row, formatted with thousands separator | Row hidden |
| Specs | `loa_length` | Key-value row, "X m" | Row hidden |
| Specs | `beam` | Key-value row, "X m" | Row hidden |
| Specs | `draft` | Key-value row, "X m" | Row hidden |
| Specs | `engine_power` | Key-value row | Row hidden |
| Specs | `trading_area` | Key-value row | Row hidden |
| Description | `marketing_description` | Rich text render | Section hidden |
| CTA | `status` = `open` | Show all 3 CTAs (WhatsApp, Email, Form) | — |
| CTA | `status` = `closed` | Disable CTAs; show "Closed" message | — |

> **NEVER shown on detail page:** `real_vessel_name`, `imo_number`, `owner_details`, `price_commercial_terms` (ADR-005, AC-4). These fields exist in `jsl_vessel_listings` but are excluded by the public projection. Likewise, no data from the `jsl_vessel_certificates` entity (CR-001-001) is ever queried or rendered on this page. All of this is visible only in the CMS (see §12.6, §12.6a).

#### 6.6.2 Inquiry CTA Link Generation

| CTA | Link Format | Prefilled Content | Source |
|-----|------------|-------------------|--------|
| WhatsApp | `https://wa.me/{broker_wa}?text={message}` | "Halo Jaya Sakti Line, saya tertarik dengan kapal JSL-001." | PRD §16.1, broker WA from `.env` |
| Email | `mailto:{broker_email}?subject={subject}` | Subject: "Inquiry: Kapal JSL-001" | PRD §16.1, broker email from `.env` |
| Form | Inline form (see wireframe above) | vessel_listing_id auto-attached (hidden) | PRD §16.1 |

> The prefilled message and subject use the `public_ref_code` (e.g., "JSL-001"), NEVER the real vessel name (ADR-005).

### 6.7 Gallery Page

**Route:** `GET /gallery` → `GalleryController@index`
**Data sources:** `jsl_gallery_items` (where `deleted_at IS NULL`, ordered by `sort_order`)
**PRD reference:** FR-06, §14.3
**ARD reference:** ARD §6 Gallery Component, ADR-009 (Media)

```
┌─────────────────────────────────┐
│  [Header]                        │
├─────────────────────────────────┤
│                                 │
│  PAGE HEADER                    │
│  Galeri                         │
│  (H1)                           │
│                                 │
├─────────────────────────────────┤
│  CATEGORY FILTER (optional)     │
│  [All] [Kapal] [Operasional]    │
│  [Kegiatan] ...                 │
│  (Horizontal scroll on mobile,  │
│   inline pills on desktop)      │
│  [RECOMMENDATION — if categories│
│   are used by admin]            │
│                                 │
├─────────────────────────────────┤
│  GALLERY GRID                   │
│                                 │
│  ┌──┐ ┌──┐ ┌──┐               │
│  │  │ │  │ │  │               │
│  └──┘ └──┘ └──┘               │
│  ┌──┐ ┌──┐ ┌──┐               │
│  │  │ │  │ │  │               │
│  └──┘ └──┘ └──┘               │
│  (2-3 columns on mobile,        │
│   3-4 columns on desktop)       │
│                                 │
│  (Click image → lightbox with   │
│   caption overlay)              │
│                                 │
│  [Load More] or pagination      │
│                                 │
├─────────────────────────────────┤
│  [Footer]                        │
└─────────────────────────────────┘
```

### 6.8 Contact Page

**Route:** `GET /contact` → `ContactController@index`
**Data sources:** `jsl_site_settings` (display contact info, social links)
**PRD reference:** FR-05, §16.2
**ARD reference:** ARD §6 Inquiry Component

```
┌─────────────────────────────────┐
│  [Header]                        │
├─────────────────────────────────┤
│                                 │
│  PAGE HEADER                    │
│  Hubungi Kami                   │
│  (H1)                           │
│                                 │
├─────────────────────────────────┤
│  CONTACT INFO + CTA             │
│                                 │
│  ┌─────────────────────────┐   │
│  │  📞 Telepon              │   │
│  │  +62 xxx (display)       │   │
│  │                          │   │
│  │  ✉ Email                 │   │
│  │  info@jayasaktiline...   │   │
│  │                          │   │
│  │  📍 Alamat               │   │
│  │  Office address...       │   │
│  └─────────────────────────┘   │
│                                 │
│  [WhatsApp CTA]  [Email CTA]    │
│  (Same link generation as       │
│   vessel detail, but no vessel  │
│   reference)                    │
│                                 │
├─────────────────────────────────┤
│  GENERAL INQUIRY FORM           │
│                                 │
│  ┌─────────────────────────┐   │
│  │  Form Inquiry Umum       │   │
│  │                          │   │
│  │  Nama *                  │   │
│  │  [________________]      │   │
│  │                          │   │
│  │  Perusahaan              │   │
│  │  [________________]      │   │
│  │                          │   │
│  │  Email                   │   │
│  │  [________________]      │   │
│  │                          │   │
│  │  Phone                   │   │
│  │  [________________]      │   │
│  │  (Email or Phone req.)   │   │
│  │                          │   │
│  │  Pesan *                 │   │
│  │  [________________]      │   │
│  │  [________________]      │   │
│  │                          │   │
│  │  ☐ Saya menyetujui       │   │
│  │    penggunaan data... *  │   │
│  │                          │   │
│  │  [Kirim]                 │   │
│  └─────────────────────────┘   │
│  (vessel_listing_id = NULL     │
│   for general contact)          │
│                                 │
├─────────────────────────────────┤
│  [Footer]                        │
└─────────────────────────────────┘
```

### 6.9 Success / Confirmation States

#### 6.9.1 Inquiry Form Success

**Trigger:** After successful form submission (POST /inquiries)
**PRD reference:** AC-7

```
┌─────────────────────────────────┐
│  [Header]                        │
├─────────────────────────────────┤
│                                 │
│  ┌─────────────────────────┐   │
│  │                          │   │
│  │    ✅ (Success icon)     │   │
│  │                          │   │
│  │  Inquiry Terkirim!       │   │
│  │  (H2, green color)       │   │
│  │                          │   │
│  │  Terima kasih. Kami akan │   │
│  │  menghubungi Anda segera.│   │
│  │                          │   │
│  │  [Kembali ke Beranda]    │   │
│  │  [Lihat Kapal Lain]      │   │
│  │                          │   │
│  └─────────────────────────┘   │
│                                 │
├─────────────────────────────────┤
│  [Footer]                        │
└─────────────────────────────────┘
```

#### 6.9.2 Form Validation Error State

| Element | Error Display |
|---------|--------------|
| Field with error | Red border (`#DC2626`), error message below field in red text |
| Error message | Specific, actionable: "Nama wajib diisi.", "Email atau telepon harus diisi salah satu." |
| Form | Does not clear; user's input is preserved |
| Page | Scrolls to first error field |

### 6.10 404 / Not Found Page

```
┌─────────────────────────────────┐
│  [Header]                        │
├─────────────────────────────────┤
│                                 │
│  ┌─────────────────────────┐   │
│  │                          │   │
│  │  404                     │   │
│  │  (H1, large)             │   │
│  │                          │   │
│  │  Halaman tidak ditemukan │   │
│  │                          │   │
│  │  [Kembali ke Beranda]    │   │
│  │                          │   │
│  └─────────────────────────┘   │
│                                 │
├─────────────────────────────────┤
│  [Footer]                        │
└─────────────────────────────────┘
```

---

## 7. Public Website — Responsive Specification

**References: PRD FR-11 (Responsive), PRD AC-1, ARD §17 (Quality Attributes — Performance), ADR-009 (Responsive images)**

### 7.1 Responsive Layout Rules

| Element | Mobile (<768px) | Tablet (768-1023px) | Desktop (≥1024px) |
|---------|-----------------|---------------------|-------------------|
| Header | Hamburger menu, 56px height | Hamburger or horizontal nav | Horizontal nav, 64px height |
| Hero | Full-width, min-height 50vh | Full-width, min-height 50vh | Full-width, min-height 60vh |
| Content max-width | 100% (16px padding) | 720px (24px padding) | 1200px (centered) |
| Vessel cards | 1 column | 2 columns | 3 columns |
| Service cards | 1 column | 2 columns | 3 columns |
| Gallery grid | 2 columns | 3 columns | 4 columns |
| Specs table | 1 column (key-value) | 2 columns | 2 columns |
| Footer | Stacked (1 column) | 2 columns | 4 columns |
| Inquiry form | Full-width inputs | Full-width inputs | Max 600px centered |
| Pagination | Prev/Next only | Numbered | Numbered with ellipsis |

### 7.2 Image Responsive Strategy

**Reference: ADR-009 (Media Storage — Responsive Variants), DBD §14 (Media Strategy)**

| Image Usage | Variant | Max Width | Format | Loading |
|-------------|---------|-----------|--------|---------|
| Vessel card thumbnail | `variant_thumbnail_path` | ~400px | WebP (JPEG fallback) | Lazy |
| Vessel detail main image | `variant_medium_path` / `variant_large_path` | ~800px / ~1200px | WebP (JPEG fallback) | Eager (first image), Lazy (rest) |
| Gallery thumbnail | `variant_thumbnail_path` | ~400px | WebP (JPEG fallback) | Lazy |
| Gallery lightbox | `variant_large_path` | ~1200px | WebP (JPEG fallback) | Lazy (on click) |
| Service icon | `variant_thumbnail_path` | ~100px | WebP (JPEG fallback) | Eager (above fold) |
| Hero background | `variant_large_path` or custom | ~1920px | WebP (JPEG fallback) | Eager |

> **srcset:** All images use `srcset` with multiple variant widths to serve the appropriate size for the viewport. Example:
> `<img srcset="/img/JSL-001_thumb.webp 400w, /img/JSL-001_medium.webp 800w, /img/JSL-001_large.webp 1200w" sizes="(max-width: 768px) 100vw, 33vw" src="/img/JSL-001_medium.webp" alt="Bulk Carrier JSL-001" loading="lazy">`

> **Alt text rule (ADR-005, BC-6):** Alt text MUST NOT contain sensitive data (real vessel name, IMO, owner). Alt text uses the `public_ref_code` and vessel type, e.g., "Bulk Carrier JSL-001".

### 7.3 Touch Targets

| Element | Minimum Size | Source |
|---------|-------------|--------|
| Nav links (mobile) | 44×44px | WCAG 2.1 AA (UX-8) |
| CTA buttons | 44×44px | WCAG 2.1 AA |
| Form inputs | 44px height | WCAG 2.1 AA |
| Thumbnail strip images | 48×48px | — |
| Pagination buttons | 44×44px | WCAG 2.1 AA |

### 7.4 Performance Budget

| Metric | Target | Source |
|--------|--------|--------|
| LCP (Largest Contentful Paint) | < 3s on 4G mobile | PRD AC-8, ARD §17 |
| Image format | WebP with JPEG fallback | ADR-009 |
| Lazy loading | All below-fold images | PRD NFR (Performance), ADR-009 |
| CSS | Single minified stylesheet (Tailwind build) | Performance |
| JS | Minimal; GA4 tag + lightbox only | Performance |
| Font loading | `font-display: swap` | Performance |

---

## 8. Public Website — Accessibility Specification

**References: PRD NFR (Accessibility — WCAG 2.1 AA), ADR-005 (alt text must not leak sensitive data), ARD §17 (Quality Attributes)**

### 8.1 WCAG 2.1 AA Compliance Checklist

| Category | Requirement | Implementation |
|----------|-------------|----------------|
| **Perceivable** | | |
| 1.1.1 Non-text Content | All images have alt text | Alt text uses `public_ref_code` + vessel type; NEVER real vessel name (ADR-005) |
| 1.3.1 Info and Relationships | Semantic HTML | Use `<nav>`, `<main>`, `<article>`, `<section>`, `<h1>`-`<h6>`, `<ul>`/`<ol>` |
| 1.4.3 Contrast (Minimum) | 4.5:1 for normal text, 3:1 for large text | All color combinations verified (see §3.1) |
| 1.4.4 Resize Text | Text resizable to 200% without loss | Use `rem` units, responsive breakpoints |
| 1.4.11 Non-text Contrast | UI components 3:1 minimum | Button borders, form inputs, focus indicators |
| **Operable** | | |
| 2.1.1 Keyboard | All functionality keyboard-accessible | Tab navigation, Enter/Space on buttons, Esc to close menus/modals |
| 2.1.2 No Keyboard Trap | Focus can move away from components | Lightbox: Esc closes; mobile menu: Esc closes |
| 2.4.3 Focus Order | Logical tab order | DOM order matches visual order |
| 2.4.7 Focus Visible | Visible focus indicator | 2px outline in Navy Blue (`#0137A1`) on all focusable elements |
| **Understandable** | | |
| 3.2.1 On Focus | No unexpected context change on focus | — |
| 3.2.2 On Input | No unexpected context change on input | — |
| 3.3.1 Error Identification | Clear error messages | Red border + text below field (see §6.9.2) |
| 3.3.2 Labels or Instructions | All form fields have labels | `<label>` associated with each input |
| **Robust** | | |
| 4.1.1 Parsing | Valid HTML | — |
| 4.1.2 Name, Role, Value | ARIA where needed | Lightbox: `role="dialog"`, `aria-label`; hamburger: `aria-expanded` |

### 8.2 Focus States

| Element | Focus Style |
|---------|-------------|
| Links | 2px outline `#0137A1`, 2px offset |
| Buttons | 2px outline `#0137A1`, 2px offset |
| Form inputs | 2px outline `#0137A1`, border color change to `#0137A1` |
| Vessel cards | 2px outline `#0137A1` on the card container |

### 8.3 Skip Navigation

Each page includes a visually hidden "Skip to main content" link as the first focusable element, visible on focus.

```
<a href="#main-content" class="sr-only focus:not-sr-only">
    Skip to main content
</a>
```

---

## 9. Public Website — SEO & Social Sharing Spec

**References: PRD FR-10 [RECOMMENDATION], ARD §12.2 (Public Routes), DBD §3 (Traceability)**

### 9.1 Meta Tags per Page

| Page | Title Template | Meta Description Source | OG Image |
|------|----------------|------------------------|----------|
| Home | "{site_name} — {tagline}" | `jsl_site_settings` tagline or custom | Hero image or brand image |
| About | "Tentang Kami — {site_name}" | First 160 chars of `about` field | About hero image |
| Services | "Layanan — {site_name}" | Custom or first 160 chars of first service | First service icon or brand image |
| Vessel Index | "Perdagangan Kapal — {site_name}" | Custom description | First vessel thumbnail |
| Vessel Detail | "{vessel_type} {public_ref_code} — {site_name}" | First 160 chars of `marketing_description` | First vessel image (variant_large) |
| Gallery | "Galeri — {site_name}" | Custom description | First gallery image |
| Contact | "Hubungi Kami — {site_name}" | Custom description | Brand image |

### 9.2 Open Graph Tags

```html
<!-- Per page (example: Vessel Detail) -->
<meta property="og:type" content="website">
<meta property="og:title" content="Bulk Carrier JSL-001 — Jaya Sakti Line">
<meta property="og:description" content="First 160 chars of marketing_description...">
<meta property="og:image" content="https://domain.com/storage/jsl-media/vessel-listings/JSL-001_large.webp">
<meta property="og:url" content="https://domain.com/vessels/JSL-001">
<meta property="og:site_name" content="Jaya Sakti Line">
<meta name="twitter:card" content="summary_large_image">
```

> **ADR-005 constraint:** OG tags MUST NOT include sensitive data. OG title uses `public_ref_code`, not real vessel name. OG image is EXIF-stripped (ADR-009).

### 9.3 Sitemap

| URL | Priority | Change Frequency | Source |
|-----|----------|-----------------|--------|
| `/` | 1.0 | Weekly | Static |
| `/about` | 0.8 | Monthly | Static |
| `/services` | 0.8 | Monthly | Static |
| `/vessels` | 0.9 | Weekly | Static |
| `/vessels/{public_ref_code}` | 0.7 | Weekly | Dynamic (per listing) |
| `/gallery` | 0.6 | Monthly | Static |
| `/contact` | 0.5 | Monthly | Static |

> Sitemap.xml is auto-generated via a scheduled task (ARD §20, FR-10). All public URLs are included. CMS/admin URLs are excluded.

### 9.4 URL Structure

| Route | URL | SEO Feature |
|-------|-----|-------------|
| Home | `/` | — |
| About | `/about` | Clean URL |
| Services | `/services` | Clean URL |
| Vessel index | `/vessels` | Clean URL |
| Vessel detail | `/vessels/JSL-001` | Clean URL with `public_ref_code` |
| Gallery | `/gallery` | Clean URL |
| Contact | `/contact` | Clean URL |

> All public URLs use lowercase, hyphenated, SEO-friendly paths (PRD FR-10). The `public_ref_code` is used in the vessel detail URL, not the database ID or real vessel name (ADR-005).

---

## 10. Public Website — Analytics & Conversion Tracking Spec

**References: PRD §3.3 (Tracking note), PRD AC-15, PRD §16.3 (WA/Email tracking)**

### 10.1 GA4 Events

| Event Name | Trigger | Parameters | Source |
|------------|---------|------------|--------|
| `page_view` | Every page load | `page_title`, `page_location` | GA4 default |
| `inquiry_whatsapp_click` | WhatsApp CTA click | `vessel_ref` (public_ref_code) | PRD §16.3, AC-15 |
| `inquiry_email_click` | Email CTA click | `vessel_ref` (public_ref_code) | PRD §16.3, AC-15 |
| `inquiry_form_submit` | Inquiry form successfully submitted | `vessel_ref` (public_ref_code or "general") | PRD §16.3, AC-15 |
| `vessel_detail_view` | Vessel detail page load | `vessel_ref`, `vessel_type`, `status` | Analytics KPI |
| `vessel_index_filter` | Filter applied on vessel index | `filter_type`, `filter_value` | Analytics KPI |

### 10.2 Implementation

- GA4 tag loaded via Google Tag Manager or direct gtag.js snippet in Blade layout `<head>`.
- Events pushed via `dataLayer` or `gtag('event', ...)` on client-side interactions.
- WhatsApp/Email clicks: JavaScript event listener on CTA links, fires event before navigation.
- Form submit: Event fired on successful server response (redirect to success page with event trigger).

---

## 11. CMS Panel — Layout & Navigation

**References: ADR-003 (Dedicated CMS Panel), ADR-008 (Filament as CMS), ARD §6 (CMS Components), ARD §12.3 (CMS Routes)**

### 11.1 Panel Configuration

| Property | Value | Source |
|----------|-------|--------|
| Panel ID | `cms` | ADR-003 (4th panel) |
| Path | `/cms` | ARD §12.3 |
| Login Path | `/cms/login` | — |
| Auth Guard | `web` (existing) | ADR-003, ADR-008 |
| Role | `cms` (Spatie Permission) | ADR-008, DBD §13.9 |
| ScopeByBranch | **EXCLUDED** | ADR-003 (critical) |
| Brand Name | "JSL Website CMS" | Distinct from operational panels |
| Primary Color | `#0137A1` | Brand consistency |
| Theme | Light mode (no dark mode) | Consistent with existing panels |

### 11.2 Existing Panel Reference

The CMS panel is the 4th Filament panel, alongside:

| # | Panel ID | Path | Purpose | ScopeByBranch |
|---|----------|------|---------|---------------|
| 1 | `admin` | `/admin` | Operational admin | ✅ Yes |
| 2 | `fc` | `/fc` | Field coordinator | ✅ Yes |
| 3 | `customer` | `/portal` | Customer portal | ❌ No |
| 4 | `cms` (new) | `/cms` | Website CMS | ❌ No (ADR-003) |

### 11.3 CMS Navigation Structure

```
CMS Panel (/cms)
│
├── Dashboard
│   (Quick counts: vessel listings, inquiries, services, gallery items)
│
├── Konten Website (Website Content)
│   ├── Company Profile
│   ├── Services
│   ├── Gallery
│   └── Site Settings
│
├── Perdagangan Kapal (Vessel Trading)
│   └── Vessel Listings
│
└── Inquiry
    └── Inquiry Inbox
```

### 11.4 CMS Page Map

| Nav Item | Filament Resource | Route | DBD Table |
|----------|-------------------|-------|-----------|
| Dashboard | Dashboard page | `/cms` | (aggregate counts) |
| Company Profile | CompanyProfileResource | `/cms/company-profiles` | `jsl_company_profiles` |
| Services | ServiceResource | `/cms/services` | `jsl_services` |
| Gallery | GalleryItemResource | `/cms/gallery-items` | `jsl_gallery_items` |
| Site Settings | SiteSettingsResource | `/cms/site-settings` | `jsl_site_settings` |
| Vessel Listings | VesselListingResource | `/cms/vessel-listings` | `jsl_vessel_listings`, `jsl_vessel_images`, `jsl_vessel_certificates` |
| Inquiry Inbox | InquiryResource | `/cms/inquiries` | `jsl_inquiries` |

---

## 12. CMS Panel — Wireframes

**References: ADR-003 (Dedicated CMS Panel), ADR-008 (Filament v3), ARD §6 (CMS Components), DBD §5 (Entities), DBD §13 (Physical Data Model)**

> **Wireframe convention:** CMS wireframes reflect Filament v3 UI patterns. Filament provides built-in Table, Form, FileUpload, RichEditor, Toggle, and Repeater components. These wireframes show the information architecture and field organization, not custom visual design.

### 12.1 Login

```
┌─────────────────────────────────┐
│                                 │
│        JSL Logo                 │
│        JSL Website CMS          │
│                                 │
│  ┌─────────────────────────┐   │
│  │  Email                   │   │
│  │  [________________]      │   │
│  │                          │   │
│  │  Password                │   │
│  │  [________________]      │   │
│  │                          │   │
│  │  [Masuk]                 │   │
│  └─────────────────────────┘   │
│                                 │
│  (Rate-limited per ADR-008)     │
│                                 │
└─────────────────────────────────┘
```

### 12.2 Dashboard

**PRD reference:** PRD §13 [RECOMMENDATION] (Dashboard quick counts)
**Data sources:** Count queries on `jsl_vessel_listings`, `jsl_inquiries`, `jsl_services`, `jsl_gallery_items`

```
┌─────────────────────────────────────────────────────────────────┐
│  [JSL Logo]  JSL Website CMS              [User Menu] [Logout]  │
│─────────────────────────────────────────────────────────────────│
│  │                                                             │
│ S│  Dashboard                                                  │
│ i│                                                             │
│ d│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐      │
│ e│  │ Vessel   │ │ Inquiry  │ │ Services │ │ Gallery  │      │
│ b│  │ Listings │ │ (New)    │ │ Active   │ │ Items    │      │
│ a│  │          │ │          │ │          │ │          │      │
│ r│  │   12     │ │    3     │ │    8     │ │   24     │      │
│  │  │  (8 Open)│ │          │ │          │ │          │      │
│  │  └──────────┘ └──────────┘ └──────────┘ └──────────┘      │
│  │                                                             │
│  │  Recent Inquiries                                           │
│  │  ┌─────────────────────────────────────────────────────┐   │
│  │  │ Name        │ Vessel   │ Status   │ Date             │   │
│  │  │─────────────│──────────│──────────│──────────────────│   │
│  │  │ Andi        │ JSL-001  │ New      │ 2026-06-30 14:00 │   │
│  │  │ Budi        │ JSL-003  │ New      │ 2026-06-30 10:30 │   │
│  │  │ Citra       │ (Umum)   │ Read     │ 2026-06-29 16:00 │   │
│  │  └─────────────────────────────────────────────────────┘   │
│  │                                                             │
│  │  [Lihat Semua Inquiry →]                                    │
│  │                                                             │
└─────────────────────────────────────────────────────────────────┘
```

### 12.3 Company Profile Editor

**Data source:** `jsl_company_profiles` (singleton)
**PRD reference:** FR-01, §14.1
**DBD reference:** DBD §13.1

```
┌─────────────────────────────────────────────────────────────────┐
│  [Sidebar]   Company Profile                    [Save]          │
│─────────────────────────────────────────────────────────────────│
│  │                                                             │
│ S│  Konten (ID)                                                │
│ i│                                                             │
│ d│  About                                                      │
│ e│  ┌─────────────────────────────────────────────────────┐   │
│ b│  │ [RichEditor: about]                                   │   │
│ a│  │  B I U | H1 H2 H3 | • — | 🔗 🖼                      │   │
│ r│  │                                                       │   │
│  │  │  (Rich text editing area)                             │   │
│  │  └─────────────────────────────────────────────────────┘   │
│  │                                                             │
│  │  Overview                                                   │
│  │  ┌─────────────────────────────────────────────────────┐   │
│  │  │ [RichEditor: overview]                                │   │
│  │  └─────────────────────────────────────────────────────┘   │
│  │                                                             │
│  │  Vision                                                     │
│  │  ┌─────────────────────────────────────────────────────┐   │
│  │  │ [RichEditor: vision]                                  │   │
│  │  └─────────────────────────────────────────────────────┘   │
│  │                                                             │
│  │  Mission                                                    │
│  │  ┌─────────────────────────────────────────────────────┐   │
│  │  │ [RichEditor: mission]                                 │   │
│  │  └─────────────────────────────────────────────────────┘   │
│  │                                                             │
│  │  ─────────────────────────────────────────────────────     │
│  │                                                             │
│  │  Konten (EN) — optional, if EN enabled [AR-24]             │
│  │                                                             │
│  │  About (EN)                                                 │
│  │  [RichEditor: about_en] (nullable)                          │
│  │                                                             │
│  │  Overview (EN)                                              │
│  │  [RichEditor: overview_en] (nullable)                       │
│  │                                                             │
│  │  Vision (EN)                                                │
│  │  [RichEditor: vision_en] (nullable)                         │
│  │                                                             │
│  │  Mission (EN)                                               │
│  │  [RichEditor: mission_en] (nullable)                        │
│  │                                                             │
│  │  [Save]                                                     │
│  │                                                             │
└─────────────────────────────────────────────────────────────────┘
```

### 12.4 Services Manager

**Data source:** `jsl_services`
**PRD reference:** FR-02, §14.2
**DBD reference:** DBD §13.3

#### 12.4.1 Services List

```
┌─────────────────────────────────────────────────────────────────┐
│  [Sidebar]   Services                [+ New Service]           │
│─────────────────────────────────────────────────────────────────│
│  │                                                             │
│ S│  ┌─────────────────────────────────────────────────────┐   │
│ i│  │ Search...                              [Filter ▼]   │   │
│ d│  └─────────────────────────────────────────────────────┘   │
│ e│                                                             │
│ b│  ┌─────────────────────────────────────────────────────┐   │
│ a│  │ Title          │ Visible │ Order │ Created           │   │
│ r│  │────────────────│─────────│───────│───────────────────│   │
│  │  │ Ship Charter   │ ✅      │ 1     │ 2026-06-28        │   │
│  │  │ Vessel Sale    │ ✅      │ 2     │ 2026-06-28        │   │
│  │  │ Towing Service │ ❌      │ 3     │ 2026-06-29        │   │
│  │  └─────────────────────────────────────────────────────┘   │
│  │                                                             │
│  │  [Edit] [Delete] (per row)                                  │
│  │                                                             │
│  │  « 1 2 3 »                                                  │
│  │                                                             │
└─────────────────────────────────────────────────────────────────┘
```

#### 12.4.2 Service Editor

```
┌─────────────────────────────────────────────────────────────────┐
│  [Sidebar]   Edit Service                       [Save]          │
│─────────────────────────────────────────────────────────────────│
│  │                                                             │
│ S│  Konten (ID)                                                │
│ i│                                                             │
│ d│  Title *                                                    │
│ e│  [_______________________________]                          │
│ b│                                                             │
│ a│  Description                                                │
│ r│  [RichEditor: description]                                  │
│  │                                                             │
│  │  Icon/Image (optional)                                      │
│  │  [FileUpload: media_asset_id]                               │
│  │  (EXIF stripped, responsive variants generated)            │
│  │                                                             │
│  │  Display Control                                            │
│  │  Visible: [Toggle: is_visible]                              │
│  │  Sort Order: [___]                                          │
│  │                                                             │
│  │  ─────────────────────────────────────────────────────     │
│  │                                                             │
│  │  Konten (EN) — optional [AR-24]                             │
│  │                                                             │
│  │  Title (EN)                                                 │
│  │  [_______________________________] (nullable)               │
│  │                                                             │
│  │  Description (EN)                                           │
│  │  [RichEditor: description_en] (nullable)                    │
│  │                                                             │
│  │  [Save]  [Cancel]                                           │
│  │                                                             │
└─────────────────────────────────────────────────────────────────┘
```

### 12.5 Vessel Listings Manager (List View)

**Data source:** `jsl_vessel_listings`
**PRD reference:** FR-08, AC-11
**DBD reference:** DBD §13.4

```
┌─────────────────────────────────────────────────────────────────┐
│  [Sidebar]   Vessel Listings           [+ New Listing]        │
│─────────────────────────────────────────────────────────────────│
│  │                                                             │
│ S│  ┌─────────────────────────────────────────────────────┐   │
│ i│  │ Search...                    [Status ▼] [Type ▼]    │   │
│ d│  └─────────────────────────────────────────────────────┘   │
│ e│                                                             │
│ b│  ┌─────────────────────────────────────────────────────┐   │
│ a│  │ Ref Code  │ Type         │ Status │ Images │ Created │   │
│ r│  │───────────│──────────────│────────│────────│─────────│   │
│  │  │ JSL-001   │ Bulk Carrier │ Open   │  4/6   │ Jun 28  │   │
│  │  │ JSL-002   │ Tugboat      │ Open   │  2/6   │ Jun 28  │   │
│  │  │ JSL-003   │ Barge        │ Closed │  6/6   │ Jun 29  │   │
│  │  └─────────────────────────────────────────────────────┘   │
│  │                                                             │
│  │  [Edit] [Toggle Status] [Delete] (per row)                  │
│  │  [Restore] (in trashed filter)                              │
│  │                                                             │
│  │  « 1 2 3 »                                                  │
│  │                                                             │
└─────────────────────────────────────────────────────────────────┘
```

### 12.6 Vessel Listing Editor (Create/Edit)

**Data source:** `jsl_vessel_listings` (including SENSITIVE fields), `jsl_vessel_images`, `jsl_media_assets`
**PRD reference:** FR-08, AC-11, §15
**DBD reference:** DBD §13.4, §13.5, §15.2
**CRITICAL:** This is the CMS context — the admin CAN see and edit sensitive fields (ADR-005: CMS has full entity access).
**Note (CR-001-001):** Certificate management is no longer an inline field in this editor — it is a dedicated **Certificates tab**, documented separately in §12.6a, backed by the `jsl_vessel_certificates` child entity (DBD §5.2.3).

```
┌─────────────────────────────────────────────────────────────────┐
│  [Sidebar]   Edit Vessel Listing               [Save]          │
│─────────────────────────────────────────────────────────────────│
│  │                                                             │
│ S│  ┌─── PUBLIC INFORMATION ────────────────────────────────┐  │
│ i│  │  (These fields are visible on the public website)      │  │
│ d│  │                                                         │  │
│ e│  │  Reference Code *                                       │  │
│ b│  │  [JSL-001____________] (unique)                        │  │
│ a│  │                                                         │  │
│ r│  │  Vessel Type *                                          │  │
│  │  │  [Select: Bulk Carrier / Tugboat / Barge / Other]      │  │
│  │  │                                                         │  │
│  │  │  Year Built                                             │  │
│  │  │  [____] (1900–current year, nullable)                  │  │
│  │  │                                                         │  │
│  │  │  Flag Registry                                          │  │
│  │  │  [________________] (nullable)                         │  │
│  │  │                                                         │  │
│  │  │  Gross Tonnage                                          │  │
│  │  │  [________] (nullable)                                 │  │
│  │  │                                                         │  │
│  │  │  Deadweight                                             │  │
│  │  │  [________] (nullable)                                 │  │
│  │  │                                                         │  │
│  │  │  LOA Length (m)                                         │  │
│  │  │  [________] (nullable)                                 │  │
│  │  │                                                         │  │
│  │  │  Beam (m)                                               │  │
│  │  │  [________] (nullable)                                 │  │
│  │  │                                                         │  │
│  │  │  Draft (m)                                              │  │
│  │  │  [________] (nullable)                                 │  │
│  │  │                                                         │  │
│  │  │  Engine Power                                           │  │
│  │  │  [________________] (nullable)                         │  │
│  │  │                                                         │  │
│  │  │  Trading Area                                           │  │
│  │  │  [________________] (nullable)                         │  │
│  │  │                                                         │  │
│  │  │  Marketing Description (ID)                             │  │
│  │  │  [RichEditor: marketing_description]                   │  │
│  │  │                                                         │  │
│  │  │  Marketing Description (EN)                             │  │
│  │  │  [RichEditor: marketing_description_en] (nullable)     │  │
│  │  │                                                         │  │
│  │  │  Status *                                               │  │
│  │  │  [Select: Open / Closed] (default: Open)               │  │
│  │  │                                                         │  │
│  │  └─────────────────────────────────────────────────────────┘  │
│  │                                                             │
│  │  ┌─── VESSEL IMAGES (max 6) ─────────────────────────────┐  │
│  │  │                                                         │  │
│  │  │  [FileUpload + Repeater]                                │  │
│  │  │                                                         │  │
│  │  │  ┌──────────┐  sort_order: 1  alt_text: [_________]   │  │
│  │  │  │ [Image]   │                              [Remove]    │  │
│  │  │  └──────────┐                                           │  │
│  │  │  ┌──────────┐  sort_order: 2  alt_text: [_________]   │  │
│  │  │  │ [Image]   │                              [Remove]    │  │
│  │  │  └──────────┘                                           │  │
│  │  │  ...                                                    │  │
│  │  │                                                         │  │
│  │  │  [+ Add Image] (disabled if 6 images already)          │  │
│  │  │                                                         │  │
│  │  │  ⚠ Alt text MUST NOT contain vessel real name,         │  │
│  │  │    IMO, or owner info. Use public_ref_code + type.     │  │
│  │  │    (ADR-005, BC-6)                                      │  │
│  │  │                                                         │  │
│  │  └─────────────────────────────────────────────────────────┘  │
│  │                                                             │
│  │  ┌─── SENSITIVE / CONFIDENTIAL (Internal Only) ──────────┐  │
│  │  │  ⚠ These fields are NEVER shown on the public          │  │
│  │  │    website. They are for internal broker reference     │  │
│  │  │    only. Protected by Public Projection Pattern.       │  │
│  │  │    (ADR-005, PRD AC-4)                                  │  │
│  │  │                                                         │  │
│  │  │  Real Vessel Name                                       │  │
│  │  │  [________________] (nullable, SENSITIVE)              │  │
│  │  │                                                         │  │
│  │  │  IMO Number                                             │  │
│  │  │  [________________] (nullable, SENSITIVE)              │  │
│  │  │                                                         │  │
│  │  │  Owner Details                                          │  │
│  │  │  [________________] (nullable, SENSITIVE)              │  │
│  │  │  [________________]                                     │  │
│  │  │                                                         │  │
│  │  │  Price / Commercial Terms                               │  │
│  │  │  [________________] (nullable, CONFIDENTIAL)           │  │
│  │  │  [________________]                                     │  │
│  │  │                                                         │  │
│  │  └─────────────────────────────────────────────────────────┘  │
│  │                                                             │
│  │  [Save]  [Cancel]                                           │
│  │                                                             │
└─────────────────────────────────────────────────────────────────┘
```

> **Section separation:** The form is divided into three clearly labeled sections:
> 1. **PUBLIC INFORMATION** — fields shown on the public website (projection).
> 2. **VESSEL IMAGES** — image upload with ordering and alt text.
> 3. **SENSITIVE / CONFIDENTIAL (Internal Only)** — fields NEVER shown publicly; clearly marked with warning.
>
> This separation reinforces the ADR-005 boundary at the UI level. The admin understands which fields are public and which are internal.
>
> Certificate management is a separate concern from this form — see **§12.6a Vessel Listing — Certificates Tab**.

### 12.6a Vessel Listing — Certificates Tab

**Data source:** `jsl_vessel_certificates` (child of `jsl_vessel_listings`), `jsl_media_assets` (private disk only)
**PRD reference:** FR-12, AC-17 — added via [CR-001-001](../02-product/CR-001-001-Internal-Vessel-Certificate-Management.md)
**DBD reference:** DBD §5.2.3, §6 (Aggregate Design), §8.7 (Lifecycle), §13.6, §14.7 (private-disk storage), §15.2a (field classification)
**CRITICAL:** This entire tab is internal-only. No certificate data or document is ever queried, eager-loaded, or rendered on the public website (ADR-005, DBD §15.2a, §15.4).

> **Placement:** Shown as a tab alongside the main listing form (Filament `Tabs` component), accessible only after the vessel listing has been created (certificates belong to an existing listing). Implemented as a Filament relation manager / repeater over `jsl_vessel_certificates`.

#### 12.6a.1 Certificates List (within tab)

```
┌─────────────────────────────────────────────────────────────────┐
│  [Sidebar]   Edit Vessel Listing                                │
│  [General] [Images] [● Certificates]            [Save]          │
│─────────────────────────────────────────────────────────────────│
│  │                                                             │
│ S│  ⚠ Internal only — never shown on the public website.       │
│ i│    (ADR-005, CR-001-001)                                    │
│ d│                                                             │
│ e│  ┌─────────────────────────────────────────────────────┐   │
│ b│  │ Type          │ Number    │ Expiry      │ Status      │   │
│ a│  │───────────────│───────────│─────────────│─────────────│   │
│ r│  │ Cert. of Reg.  │ COR-1182  │ 2027-03-10  │ ✅ Valid    │   │
│  │  │ Classification │ CLS-4471  │ 2026-08-01  │ ⚠ Expiring  │   │
│  │  │ Safety Mgmt    │ SMC-0093  │ 2026-05-15  │ ❌ Expired  │   │
│  │  └─────────────────────────────────────────────────────┘   │
│  │                                                             │
│  │  [+ Add Certificate]                                        │
│  │  [Edit] [Delete] (per row)                                  │
│  │                                                             │
└─────────────────────────────────────────────────────────────────┘
```

> **Expiry highlighting [RECOMMENDATION]:** Rows are flagged ⚠ Expiring when `expiry_date` is within 30 days, and ❌ Expired when `expiry_date` has passed. This is computed at read time from `expiry_date` — it is not a stored lifecycle state (DBD §8.7).

#### 12.6a.2 Certificate Editor (Create/Edit)

```
┌─────────────────────────────────────────────────────────────────┐
│  [Modal]   Add / Edit Certificate                [Save] [Cancel]│
│─────────────────────────────────────────────────────────────────│
│                                                                   │
│   Certificate Type *                                             │
│   [Select: Certificate of Registry / Classification Certificate /│
│            Safety Management Certificate / IOPP Certificate /    │
│            Other] (app-validated allow-list, DBD §10.5)          │
│                                                                   │
│   Certificate Number                                             │
│   [________________] (nullable)                                  │
│                                                                   │
│   Issuing Authority                                               │
│   [________________] (nullable)                                  │
│                                                                   │
│   Issue Date                          Expiry Date                │
│   [____-__-__] (nullable)             [____-__-__] (nullable)    │
│   (expiry_date must be ≥ issue_date when both set — DBD VC-10)   │
│                                                                   │
│   Document (optional)                                             │
│   [FileUpload: media_asset_id] — PDF or image scan                │
│   ⚠ Stored on PRIVATE disk only. No public variant is generated. │
│     Authenticated CMS access only (DBD §14.7, DF-11).            │
│                                                                   │
│   Internal Notes                                                  │
│   [________________] (nullable, internal only)                   │
│   [________________]                                              │
│                                                                   │
│   [Save]  [Cancel]                                                │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

> **No cap on certificates per vessel** (DBD BC-12). Admin may add as many certificate records as needed.

### 12.7 Gallery Manager

**Data source:** `jsl_gallery_items`
**PRD reference:** FR-06, §14.3
**DBD reference:** DBD §13.6

#### 12.7.1 Gallery List

```
┌─────────────────────────────────────────────────────────────────┐
│  [Sidebar]   Gallery                  [+ New Gallery Item]    │
│─────────────────────────────────────────────────────────────────│
│  │                                                             │
│ S│  ┌─────────────────────────────────────────────────────┐   │
│ i│  │ Search...                    [Category ▼]            │   │
│ d│  └─────────────────────────────────────────────────────┘   │
│ e│                                                             │
│ b│  ┌──────────┐ ┌──────────┐ ┌──────────┐                  │
│ a│  │ [Image]   │ │ [Image]   │ │ [Image]   │                  │
│ r│  │ Caption 1 │ │ Caption 2 │ │ Caption 3 │                  │
│  │  │ Category  │ │ Category  │ │ Category  │                  │
│  │  │ [Edit]    │ │ [Edit]    │ │ [Edit]    │                  │
│  │  │ [Delete]  │ │ [Delete]  │ │ [Delete]  │                  │
│  │  └──────────┘ └──────────┘ └──────────┘                  │
│  │                                                             │
│  │  « 1 2 3 »                                                  │
│  │                                                             │
└─────────────────────────────────────────────────────────────────┘
```

#### 12.7.2 Gallery Item Editor

```
┌─────────────────────────────────────────────────────────────────┐
│  [Sidebar]   Edit Gallery Item                  [Save]          │
│─────────────────────────────────────────────────────────────────│
│  │                                                             │
│ S│  Image *                                                    │
│ i│  [FileUpload: media_asset_id]                               │
│ d│  (EXIF stripped, responsive variants generated)            │
│ e│                                                             │
│ b│  Caption (ID)                                               │
│ a│  [_______________________________] (nullable)               │
│ r│                                                             │
│  │  Category                                                   │
│  │  [_______________________________] (nullable) [RECOMMENDATION]│
│  │                                                             │
│  │  Caption (EN)                                               │
│  │  [_______________________________] (nullable) [AR-24]       │
│  │                                                             │
│  │  Sort Order                                                 │
│  │  [___]                                                      │
│  │                                                             │
│  │  [Save]  [Cancel]                                           │
│  │                                                             │
└─────────────────────────────────────────────────────────────────┘
```

### 12.8 Inquiry Inbox

**Data source:** `jsl_inquiries`
**PRD reference:** FR-09, AC-12, AC-13, §16
**DBD reference:** DBD §13.8

#### 12.8.1 Inquiry List

```
┌─────────────────────────────────────────────────────────────────┐
│  [Sidebar]   Inquiries                                      │
│─────────────────────────────────────────────────────────────────│
│  │                                                             │
│ S│  ┌─────────────────────────────────────────────────────┐   │
│ i│  │ Search...                    [Status ▼]               │   │
│ d│  │ [All] [New] [Read] [Contacted] [Archived]            │   │
│ e│  └─────────────────────────────────────────────────────┘   │
│ b│                                                             │
│ a│  ┌─────────────────────────────────────────────────────┐   │
│ r│  │ Name     │ Vessel   │ Status    │ Date               │   │
│  │  │──────────│──────────│───────────│────────────────────│   │
│  │  │ Andi ●   │ JSL-001  │ New       │ Jun 30, 14:00      │   │
│  │  │ Budi ●   │ JSL-003  │ New       │ Jun 30, 10:30      │   │
│  │  │ Citra    │ (Umum)   │ Read      │ Jun 29, 16:00      │   │
│  │  │ Dewi     │ JSL-001  │ Contacted │ Jun 28, 09:00      │   │
│  │  │ Eka      │ JSL-002  │ Archived  │ Jun 27, 11:00      │   │
│  │  └─────────────────────────────────────────────────────┘   │
│  │                                                             │
│  │  ● = unread (bold row)                                      │
│  │                                                             │
│  │  [View] [Mark as Read] [Mark as Contacted] [Archive]        │
│  │  (per row or batch action)                                  │
│  │                                                             │
│  │  « 1 2 3 »                                                  │
│  │                                                             │
└─────────────────────────────────────────────────────────────────┘
```

#### 12.8.2 Inquiry Detail View

```
┌─────────────────────────────────────────────────────────────────┐
│  [Sidebar]   Inquiry Detail                     [Back to List] │
│─────────────────────────────────────────────────────────────────│
│  │                                                             │
│ S│  ┌─────────────────────────────────────────────────────┐   │
│ i│  │  Name:    Andi                                         │   │
│ d│  │  Company: PT Smelter Morowali                          │   │
│ e│  │  Email:   andi@smelter.co.id                           │   │
│ b│  │  Phone:   +62 812 xxxx                                 │   │
│ a│  │  Vessel:  JSL-001 (Bulk Carrier)                       │   │
│ r│  │  Date:    Jun 30, 2026 14:00                           │   │
│  │  │  Status:  [New ▼] (change to: Read/Contacted/Archived)│   │
│  │  │  Consent: ✅ Given                                     │   │
│  │  │                                                       │   │
│  │  │  Message:                                              │   │
│  │  │  "Saya tertarik dengan kapal JSL-001.                  │   │
│  │  │   Mohon informasi lebih lanjut..."                     │   │
│  │  │                                                       │   │
│  │  └─────────────────────────────────────────────────────┘   │
│  │                                                             │
│  │  [Mark as Read]  [Mark as Contacted]  [Archive]            │
│  │  [Delete (soft)]                                            │
│  │                                                             │
│  │  ┌─── ACTIVITY LOG ──────────────────────────────────────┐ │
│  │  │  Jun 30 14:00 — Inquiry submitted (status: New)       │ │
│  │  │  (future: status changes logged here via Activity Log) │ │
│  │  └─────────────────────────────────────────────────────────┘ │
│  │                                                             │
└─────────────────────────────────────────────────────────────────┘
```

### 12.9 Site Settings

**Data source:** `jsl_site_settings` (singleton)
**PRD reference:** AR-22
**DBD reference:** DBD §13.2

```
┌─────────────────────────────────────────────────────────────────┐
│  [Sidebar]   Site Settings                      [Save]          │
│─────────────────────────────────────────────────────────────────│
│  │                                                             │
│ S│  Brand (ID)                                                 │
│ i│                                                             │
│ d│  Site Name *                                                │
│ e│  [Jaya Sakti Line___________]                              │
│ b│                                                             │
│ a│  Tagline                                                    │
│ r│  [Vessel Trading & Shipping Services___] (nullable)        │
│  │                                                             │
│  │  Footer Text                                                │
│  │  [_______________________________] (nullable)              │
│  │                                                             │
│  │  ─────────────────────────────────────────────────────     │
│  │                                                             │
│  │  Brand (EN) — optional [AR-24]                              │
│  │                                                             │
│  │  Site Name (EN)                                             │
│  │  [Jaya Sakti Line___________] (nullable)                   │
│  │                                                             │
│  │  Tagline (EN)                                               │
│  │  [_______________________________] (nullable)              │
│  │                                                             │
│  │  Footer Text (EN)                                           │
│  │  [_______________________________] (nullable)              │
│  │                                                             │
│  │  ─────────────────────────────────────────────────────     │
│  │                                                             │
│  │  Contact Display (public display only — routing values     │
│  │   are in .env per AR-22)                                    │
│  │                                                             │
│  │  Address                                                    │
│  │  [_______________________________] (nullable)              │
│  │                                                             │
│  │  Phone (display)                                            │
│  │  [+62 ___________________] (nullable)                      │
│  │                                                             │
│  │  Email (display)                                            │
│  │  [info@_________________] (nullable)                       │
│  │                                                             │
│  │  ─────────────────────────────────────────────────────     │
│  │                                                             │
│  │  Social Links                                               │
│  │                                                             │
│  │  Facebook URL                                               │
│  │  [https://facebook.com/_______] (nullable)                 │
│  │                                                             │
│  │  Instagram URL                                              │
│  │  [https://instagram.com/_______] (nullable)                │
│  │                                                             │
│  │  LinkedIn URL                                               │
│  │  [https://linkedin.com/_______] (nullable)                 │
│  │                                                             │
│  │  [Save]                                                     │
│  │                                                             │
│  │  ─────────────────────────────────────────────────────     │
│  │  ⚠ Note: Broker WhatsApp number and broker email used     │
│  │  for inquiry routing are stored in .env, NOT here.         │
│  │  (AR-22 — secrets never in database)                       │
│  │                                                             │
└─────────────────────────────────────────────────────────────────┘
```

---

## 13. CMS Panel — Responsive Specification

**References: ADR-003, ADR-008, Filament v3 defaults**

### 13.1 CMS Responsive Behavior

| Element | Mobile (<768px) | Desktop (≥768px) |
|---------|-----------------|-------------------|
| Sidebar | Collapsed (hamburger toggle) | Expandable/collapsible (Filament default) |
| Tables | Horizontal scroll | Full table with columns |
| Forms | Single column | 2-column grid where applicable |
| File upload | Full-width | Full-width within form |
| Dashboard widgets | Stacked vertically | Grid layout |

### 13.2 CMS Usage Notes

- The CMS is primarily used on desktop (Persona C — Content Admin at office). Mobile CMS usage is supported but not optimized.
- Filament v3's built-in responsive behavior is used — no custom responsive CMS design is needed.
- The CMS panel does not use response caching (ARD §12.3 — admin content must be fresh).

---

## 14. Localization (i18n) UI Spec

**References: PRD FR-01 [RECOMMENDATION] (bilingual toggle), PRD AC-16 [RECOMMENDATION], AR-24, DBD §5 (nullable EN fields), Architecture Walkthrough Note N-1, N-6**

### 14.1 Language Toggle

| Aspect | Specification |
|--------|--------------|
| Position | Header (right side, next to nav on desktop; in mobile menu) |
| Format | Two-letter code toggle: "ID" / "EN" |
| Default | ID (Indonesian) |
| Behavior | Clicking toggles all content to the selected language |
| State | Persisted in session/cookie |
| URL | No URL prefix for MVP (language is a UI toggle, not a route parameter) |
| Status | **[RECOMMENDATION] — pending stakeholder approval (PRD Open Question #2)** |

### 14.2 Content Language Display Rules

| Condition | Display | Source |
|-----------|---------|--------|
| Language = ID | Show ID content fields | `about`, `title`, `marketing_description`, etc. |
| Language = EN AND EN field is populated | Show EN content fields | `about_en`, `title_en`, `marketing_description_en`, etc. |
| Language = EN AND EN field is NULL | Fall back to ID content | Nullable fields (DBD §5) |
| UI strings (nav, buttons, labels) | Use Laravel i18n `lang/id.json` and `lang/en.json` | AR-24 |

### 14.3 Structural Readiness

If the EN toggle is NOT approved for MVP:
- The toggle is NOT shown in the header.
- All content displays in ID (default).
- The nullable EN fields exist in the database (DBD §5) but are not rendered on the public site.
- Enabling EN later is a UI toggle + content population task — no schema migration (DBD §16.5).

If the EN toggle IS approved for MVP:
- The toggle appears in the header.
- CMS forms show EN fields in a collapsible "Konten (EN)" section.
- Public site renders content in the selected language with ID fallback.

---

## 15. UX Constraints & Rules

**References: ADR-005, ADR-007, ADR-008, ADR-009, PRD AC-4, ARD §18 (Architecture Constraints)**

### 15.1 Forbidden in UX

| # | Constraint | Rationale | Source |
|---|-----------|-----------|--------|
| UX-C1 | ❌ No public wireframe or view may display sensitive vessel data (real name, IMO, owner, price) or any data/document from `jsl_vessel_certificates`. | ADR-005, AC-4. These fields/entity are excluded by the public projection. | ADR-005, PRD AC-4, CR-001-001 |
| UX-C2 | ❌ No public alt text may contain the real vessel name, IMO, or owner info. | ADR-005, BC-6. Alt text is public HTML. | ADR-005, DBD BC-6 |
| UX-C3 | ❌ No Open Graph tag may contain the real vessel name. | OG tags are public metadata. | ADR-005 |
| UX-C4 | ❌ No inquiry CTA on Closed vessels. | PRD AC-5: Closed vessels do not accept inquiries. | PRD AC-5 |
| UX-C5 | ❌ No public page may require authentication. | Public website is anonymous (ADR-007). | ADR-007, ARD R-6 |
| UX-C6 | ❌ No CMS panel may apply ScopeByBranch. | Marketing content is not branch-scoped. | ADR-003 |
| UX-C7 | ❌ No custom CMS UI framework may be used. | CMS uses Filament v3 (ADR-008). | ADR-008 |
| UX-C8 | ❌ No hardcoded brand text in Platform Kernel views. | Kernel must remain unit-agnostic. | ADR-010, ARD C-11 |
| UX-C9 | ❌ No EXIF data in public images. | EXIF stripped on upload. | ADR-005, ADR-009 |
| UX-C10 | ❌ No non-obfuscated filenames for public media. | Filenames must be non-guessable. | ADR-009 |

### 15.2 Required in UX

| # | Requirement | Rationale | Source |
|---|------------|-----------|--------|
| UX-R1 | ✅ All public vessel data display must use public projection fields only. | Structural enforcement of AC-4. | ADR-005 |
| UX-R2 | ✅ All images must use responsive srcset with variants. | Performance (AC-8) + mobile-first. | ADR-009 |
| UX-R3 | ✅ All below-fold images must be lazy-loaded. | Performance. | ADR-009, PRD NFR |
| UX-R4 | ✅ All form fields must have associated labels. | Accessibility (WCAG 2.1 AA). | PRD NFR |
| UX-R5 | ✅ All interactive elements must have visible focus states. | Accessibility (WCAG 2.1 AA). | PRD NFR |
| UX-R6 | ✅ All CTAs must trigger GA4 conversion events. | KPI measurement. | PRD AC-15 |
| UX-R7 | ✅ All public pages must include OG meta tags. | Social sharing. | PRD FR-10 |
| UX-R8 | ✅ CMS vessel listing editor must visually separate public vs. sensitive fields. | Reinforce ADR-005 boundary at UI level. | ADR-005 |
| UX-R9 | ✅ CMS vessel listing editor must warn about alt text content. | Prevent sensitive data in alt text. | ADR-005, BC-6 |
| UX-R10 | ✅ Inquiry form must include consent checkbox. | Privacy. | PRD NFR |
| UX-R11 | ✅ Inquiry form must have CSRF + spam protection (honeypot). | Security. | AR-12 |
| UX-R12 | ✅ Mobile navigation must use hamburger menu with full-screen overlay. | Mobile-first usability. | PRD FR-11 |
| UX-R13 | ✅ Vessel detail page must show 3 inquiry CTAs (WhatsApp, Email, Form) when status = Open. | PRD FR-04. | PRD FR-04 |
| UX-R14 | ✅ Vessel detail page must disable/hide inquiry CTAs when status = Closed. | PRD AC-5. | PRD AC-5 |

---

## 16. UX Traceability Matrix

**Map: PRD Requirement → ADR/ARD/DBD → UX Component**

| PRD Requirement | ADR/ARD/DBD | UX Component | UX Section |
|----------------|-------------|--------------|------------|
| FR-01 Company Profile | ADR-007, ADR-008, DBD §13.1 | About page, Company Profile Editor (CMS) | §6.3, §12.3 |
| FR-01 Bilingual toggle [RECOMMENDATION] | AR-24, DBD §5 (nullable EN) | Language toggle in header, EN fields in CMS | §14 |
| FR-02 Services Display | ADR-007, ADR-008, DBD §13.3 | Services page, Services Manager (CMS) | §6.4, §12.4 |
| FR-03 Vessel Trading listing | ADR-004, ADR-005, ADR-007, DBD §13.4 | Vessel Trading Index, Vessel Listings Manager (CMS) | §6.5, §12.5 |
| FR-03 Filtering [RECOMMENDATION] | DBD §17.6 | Filter bar on Vessel Trading Index | §6.5.1 |
| FR-03 Closed listing visibility [RECOMMENDATION] | PRD §15.3 | Closed badge on cards, CTAs disabled on detail | §6.5.3, §6.6 |
| FR-04 Inquiry per vessel (WA/Email/Form) | ADR-007, AR-15, DBD §13.8 | Inquiry CTA section on Vessel Detail, Inquiry Form | §6.6 |
| FR-05 General Contact | ADR-007, DBD §13.8 | Contact page, General Inquiry Form | §6.8 |
| FR-06 Gallery | ADR-007, ADR-008, ADR-009, DBD §13.6 | Gallery page, Gallery Manager (CMS) | §6.7, §12.7 |
| FR-06 Categories [RECOMMENDATION] | DBD §13.6 (category column) | Category filter on Gallery page | §6.7 |
| FR-07 CMS Authentication | ADR-003, ADR-008, DBD §13.9 | CMS Login page | §12.1 |
| FR-08 CMS Vessel Listing Mgmt | ADR-003, ADR-008, ADR-009, DBD §13.4, §13.5 | Vessel Listings Manager + Editor (CMS) | §12.5, §12.6 |
| FR-08 Sensitive fields internal | ADR-005, DBD §15.2 | Sensitive section in Vessel Listing Editor (CMS only) | §12.6 |
| FR-12 CMS Vessel Certificate Management (CR-001-001) | ADR-005, ADR-009, DBD §5.2.3, §13.6, §14.7, §15.2a | Certificates tab on Vessel Listing Editor (CMS only) | §12.6a |
| AC-17 Admin CRUD certificates per listing, internal-only (CR-001-001) | DBD §6.2, §8.7, §9.1 | Certificates tab — list + editor, expiry highlighting | §12.6a |
| FR-09 CMS Inquiry Inbox | ADR-003, ADR-008, DBD §13.8 | Inquiry Inbox (CMS) | §12.8 |
| FR-09 Mark inquiry status [RECOMMENDATION] | DBD §13.8 (status enum) | Status dropdown in Inquiry Detail | §12.8.2 |
| FR-09 Email notification [RECOMMENDATION] | AR-15 | (No UX — backend email, not UI) | — |
| FR-10 SEO & Sharing [RECOMMENDATION] | ADR-007, DBD §13.4 | Meta tags, OG tags, sitemap, clean URLs | §9 |
| FR-11 Performance & Responsive | ADR-007, ADR-009 | Responsive layout, responsive images, lazy load | §7 |
| AC-1 Pages render on all devices | ADR-007 | Responsive specification | §7 |
| AC-2 Vessel index thumbnail + status badge | ADR-004, ADR-005, DBD §13.4 | Vessel card with thumbnail + Open/Closed badge | §6.5.3 |
| AC-3 Vessel detail ≤6 images, general info, 3 CTAs | ADR-005, ADR-009, DBD §13.5 | Vessel Detail page with image gallery + CTA section | §6.6 |
| AC-4 No sensitive data in public | ADR-005, DBD §15.2 | All public wireframes show projection fields only | §6, §15.1 |
| AC-5 Open accepts inquiries; Closed marked | ADR-004, DBD §13.4 | Status badge; CTAs enabled/disabled by status | §6.5.3, §6.6 |
| AC-6 WhatsApp prefilled; Email prefilled | ADR-007, DBD §13.4 | CTA link generation with public_ref_code | §6.6.2 |
| AC-7 Form validation + success confirmation | ADR-007, DBD §13.8 | Form validation states, success page | §6.9 |
| AC-8 LCP < 3s on 4G mobile | ADR-009, AR-17 | Performance budget, responsive images, lazy load | §7.4 |
| AC-9 Admin login secure | ADR-003, ADR-008 | CMS Login page with rate limiting | §12.1 |
| AC-10 Admin CRUD without code changes | ADR-003, ADR-008 | All CMS managers (Company Profile, Services, Gallery) | §12.3, §12.4, §12.7 |
| AC-11 Admin CRUD vessels, ≤6 images, reorder, toggle | ADR-003, ADR-008, ADR-009, DBD §13.4, §13.5 | Vessel Listing Editor with image Repeater | §12.6 |
| AC-12 Admin view inquiries linked to vessels | ADR-003, ADR-008, DBD §13.8 | Inquiry Inbox with vessel reference column | §12.8 |
| AC-13 Admin mark inquiry status [RECOMMENDATION] | ADR-008, DBD §13.8 | Status dropdown in Inquiry Detail | §12.8.2 |
| AC-14 HTTPS, admin protected, rate-limited | ADR-003, ADR-008 | CMS panel middleware (not UX, but CMS login UX reflects it) | §12.1 |
| AC-15 GA4 + conversion events [RECOMMENDATION] | ADR-007 | GA4 event specification | §10 |
| AC-16 Bilingual toggle [RECOMMENDATION] | AR-24, DBD §5 | Language toggle spec | §14 |
| NFR Accessibility (WCAG 2.1 AA) | ADR-005, ARD §17 | Accessibility specification | §8 |
| NFR Browser support | — | Not explicitly in ARD (Walkthrough Note N-7) | §8 (implied by WCAG + responsive) |
| CMS Dashboard quick counts [RECOMMENDATION] | DBD §17.3 | Dashboard with count widgets | §12.2 |
| NFR Consent checkbox | ADR-007, DBD §13.8 | Inquiry form consent checkbox | §6.6, §6.8 |
| NFR CSRF + spam protection | AR-12 | Inquiry form (hidden CSRF + honeypot) | §15.2 |

---

## 17. Open UX Questions

These UX questions depend on PRD Open Questions (PRD §21) or stakeholder decisions. They do not block the UX specification but may require UX updates when resolved.

| # | UX Question | Depends On | Impact if Resolved |
|---|------------|------------|-------------------|
| UX-Q1 | Should the ID/EN toggle appear in the header? | PRD Open Question #2 (bilingual ID/EN) | If approved: add toggle to header (§14). If not: remove toggle, ID-only. |
| UX-Q2 | Should Closed vessels show inquiry CTAs as disabled or hide them entirely? | PRD §15.3 [RECOMMENDATION] (Closed visibility) | If "keep visible, marked closed": show disabled CTAs + message. If "hide closed": hide Closed listings from index entirely. |
| UX-Q3 | Should the inquiry form appear as a modal/overlay or as an inline expanded section? | Design preference | Minor UX change — both patterns are specified (§6.6). |
| UX-Q4 | Should the gallery support infinite scroll or pagination? | Design preference | Both are viable (§6.7). Pagination is simpler; infinite scroll is more modern. |
| UX-Q5 | Should the CMS dashboard show stale listing alerts (listings not updated in 30 days)? | PRD Risk R2 [RECOMMENDATION] | If approved: add "stale listings" widget to CMS dashboard (§12.2). |
| UX-Q6 | What is the exact domain name for the public site? | PRD Open Question #9 | Affects OG tags, sitemap URLs, canonical URLs (§9). |
| UX-Q7 | Should the vessel detail page have a "Related Vessels" section? | PRD §9 [RECOMMENDATION] (Related vessels) | If approved: add related vessels section below inquiry CTA on detail page. |

---

## 18. Glossary

| Term | Definition | Source |
|------|-----------|--------|
| **Wireframe** | A low-fidelity structural layout showing content hierarchy, component placement, and navigation. Does not prescribe visual design. | — |
| **Information Architecture** | The organization and structure of content across the website. | — |
| **User Flow** | The path a user takes through the website to accomplish a goal. | PRD §11 |
| **Design System** | A collection of reusable components, patterns, and styles that ensure visual consistency. | §3 |
| **Responsive Design** | Design approach where the layout adapts to the viewport size (mobile, tablet, desktop). | PRD FR-11 |
| **Mobile-First** | Design philosophy where mobile is the primary target, with progressive enhancement for larger screens. | PRD FR-11 |
| **CTA (Call to Action)** | A button or link that prompts the user to take a specific action (e.g., inquire, contact). | — |
| **Lightbox** | An overlay that displays a larger version of an image when a thumbnail is clicked. | — |
| **Hamburger Menu** | A navigation pattern for mobile where the menu is collapsed behind a ☰ icon. | — |
| **srcset** | An HTML attribute that provides multiple image sources at different resolutions for responsive delivery. | ADR-009 |
| **Lazy Loading** | Deferring image loading until the image is near the viewport, improving initial page load. | ADR-009 |
| **OG (Open Graph)** | Meta tags that control how a page appears when shared on social media. | PRD FR-10 |
| **GA4** | Google Analytics 4, used for tracking page views and conversion events. | PRD §3.3 |
| **Public Projection** | A read shape that structurally excludes sensitive fields from public-facing code paths. | ADR-005 |
| **WCAG 2.1 AA** | Web Content Accessibility Guidelines, level AA. The target accessibility standard for the public website. | PRD NFR |
| **Filament v3** | The admin panel framework used for the CMS. Provides built-in Table, Form, FileUpload, RichEditor, and other components. | ADR-008 |
| **Repeater** | A Filament form component that allows repeating a set of fields (used for vessel images with sort_order). | ADR-008 |
| **Touch Target** | The minimum clickable area for an interactive element on touch devices. WCAG 2.1 AA recommends 44×44px. | §7.3 |
| **Focus State** | The visual indication when an element has keyboard focus. Required by WCAG 2.1 AA. | §8.2 |
| **Skip Navigation** | A visually hidden link that allows keyboard users to skip to the main content, visible on focus. | §8.3 |
| **Singleton** | A database entity with exactly one row (e.g., CompanyProfile, SiteSettings). | DBD §5 |
| **Vessel Certificate** | An internal-only record (Certificate of Registry, Classification Certificate, etc.) attached to a vessel listing, capturing type, number, issuing authority, validity dates, an optional private document, and notes. Never exposed publicly. | CR-001-001, DBD §5.2.3 |
| **i18n (Internationalization)** | The structural readiness to support multiple languages. In this MVP: ID default, EN structurally ready. | AR-24 |
| **Breakpoint** | A viewport width at which the responsive layout changes. | §3.4 |

---

**End of UX-001 — UI/UX Specification.**

This document is the official UI/UX specification for the Jaya Sakti Line Website MVP, now at v1.1.0. It derives from PRD-001 v1.1.0 (frozen, amended via approved Change Requests), accepted ADR-001 through ADR-010, approved ARD-001, and approved DBD-001 v1.1.0 — including the Certificates tab added per approved [CR-001-001](../02-product/CR-001-001-Internal-Vessel-Certificate-Management.md). It introduces no new architecture decisions and no new database entities of its own. The next phase is **Sprint Planning** under `docs/06-sprint/`, which will break the implementation into sprints with task-level detail.
