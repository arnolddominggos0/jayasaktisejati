# Product Requirements Document (PRD)
## PRD-001 — Jaya Sakti Line Website MVP
### Company Profile & Vessel Trading MVP

**Project:** Jaya Sakti App
**Document:** PRD-001 — Jaya Sakti Line Website MVP
**Version:** 1.0.0
**Status:** ✅ FROZEN
**Document Owner:** Product Team
**Last Updated:** 2026-06-30
**Next Phase:** ARD-001 — Architecture Design Document

> **Legend used throughout this document**
> - **[CONFIRMED]** — Explicitly requested requirement; must be delivered in the MVP.
> - **[RECOMMENDATION]** — Suggested by Product based on best practice; pending stakeholder approval before being committed to MVP.
> - **[FUTURE]** — Explicitly OUT of MVP scope; listed for roadmap continuity only. Must **not** be implemented in this phase.

---

## Change History

| Version | Date | Author | Description |
|----------|------------|-------------|---------------------------|
| 1.0.0 | 2026-06-30 | Product Team | Initial MVP baseline (Frozen) |

---

## Scope Control

This PRD is frozen.

Any feature request, enhancement, or business change must be documented as a Change Request (CR) before implementation.

The development team must not modify MVP scope directly.

---

## 1. Product Vision

Jaya Sakti Group is evolving into a multi-business platform. The first MVP is **Jaya Sakti Line Website** — a public marketing and lead-generation website for the existing vessel trading & shipping services business.

**Vision Statement**

> "A credible, professional digital front door for Jaya Sakti Line that helps industrial customers, shipping companies, and vessel owners discover who we are, the services we offer, and the vessel opportunities available — and lets them contact our broker in one click."

This is **NOT** an operational shipping system. It does **not** manage brokerage operations, deals, commissions, vessel tracking, or document workflows. It is a **marketing + inquiry** platform.

---

## 2. Business Objectives

| # | Objective | Type |
|---|-----------|------|
| BO-1 | Strengthen Jaya Sakti Line branding as a credible vessel trading & shipping services player. | [CONFIRMED] |
| BO-2 | Increase company credibility in the IMIP / Morowali industrial market. | [CONFIRMED] |
| BO-3 | Support marketing activities with a public, always-on company profile. | [CONFIRMED] |
| BO-4 | Display current vessel trading opportunities to attract buyer interest. | [CONFIRMED] |
| BO-5 | Generate qualified inbound inquiries from potential customers. | [CONFIRMED] |
| BO-6 | Establish a digital foundation that can later host other Jaya Sakti business units. | [RECOMMENDATION] |

---

## 3. Success Metrics (MVP KPIs)

KPIs are intentionally **marketing-oriented**, not operational.

### 3.1 Primary KPIs (measured post-launch)
| KPI | Target (first 3 months) | Type |
|-----|--------------------------|------|
| Qualified inquiries received (WhatsApp + Email + Form) | Baseline to be set, target ≥ 20/month by month 3 | [CONFIRMED] |
| Vessel listing page views / month | ≥ 500 | [RECOMMENDATION] |
| Inquiry conversion rate (visit → inquiry) | ≥ 2% | [RECOMMENDATION] |
| Average page load time (LCP) | < 3s on 4G mobile | [RECOMMENDATION] |
| Website uptime | ≥ 99% | [RECOMMENDATION] |

### 3.2 Secondary KPIs
- Time on site ≥ 1 min 30s. [RECOMMENDATION]
- Bounce rate < 60%. [RECOMMENDATION]
- Number of vessel listings kept current (updated within 30 days). [RECOMMENDATION]

### 3.3 Tracking note
- Analytics (e.g., Google Analytics / GA4) and a basic SEO meta setup should be included. [RECOMMENDATION]
- Conversion tracking on WhatsApp/Email/Form inquiry CTAs is required to compute KPIs. [RECOMMENDATION]

---

## 4. Target Users

The website targets the following audiences, primarily in **IMIP**, **Morowali**, and surrounding industrial areas:

| Group | Description | Type |
|-------|-------------|------|
| Industrial customers | Mining / smelter / industrial companies needing shipping or vessel acquisition. | [CONFIRMED] |
| Shipping companies | Companies seeking charter, purchase, or partnership on vessels. | [CONFIRMED] |
| Vessel owners | Owners listing vessels for sale or charter through Jaya Sakti Line as broker. | [CONFIRMED] |
| General public / partners | Visitors wanting to understand the company. | [RECOMMENDATION] |

Internal users of the system:
| Group | Description | Type |
|-------|-------------|------|
| Content Admin | Manages company profile, services, gallery, vessel listings, and inquiries. | [CONFIRMED] |
| Broker / Sales | Receives inquiries; follows up with leads. | [CONFIRMED] |

---

## 5. User Personas

### Persona A — Andi (Industrial Logistics Manager, IMIP)
- **Role:** Logistics decision-maker at a smelter operation in Morowali.
- **Goal:** Find a credible shipping partner and available vessels to charter/buy.
- **Needs:** Quickly see services, browse available vessels, contact broker fast.
- **Behavior:** Mobile-first, low patience for slow sites, prefers WhatsApp.
- **Success:** Sends an inquiry within 10 minutes of landing on the site.

### Persona B — Pak Hendra (Vessel Owner)
- **Role:** Owner of a bulk carrier looking to sell or list the vessel.
- **Goal:** Gauge credibility of Jaya Sakti Line as a broker and submit a listing intent.
- **Needs:** Understand the company's track record and how to reach the broker.
- **Success:** Contacts broker via email/form to propose a vessel listing.

### Persona C — Rina (Content Admin, Jaya Sakti)
- **Role:** Marketing/Admin staff at Jaya Sakti Line.
- **Goal:** Keep the website current — update vessels, mark listings Open/Closed, manage gallery.
- **Needs:** Simple CMS, no technical skill required.
- **Success:** Updates a vessel status in < 1 minute without contacting IT.

### Persona D — Bagus (Broker / Sales)
- **Role:** Vessel broker handling inbound leads.
- **Goal:** Receive inquiries reliably and respond fast.
- **Needs:** All inquiry channels (WhatsApp/Email/Form) reach him reliably.
- **Success:** Responds to an inquiry within 1 business hour.

---

## 6. Functional Requirements

### FR-01 Public Company Profile [CONFIRMED]
- The site must present Jaya Sakti Line as a company: About, Overview, Vision & Mission, Services.
- Content is fully editable from the CMS without code changes.
- Content must support bilingual toggle (Indonesian / English). [RECOMMENDATION] — default Indonesian. [CONFIRMED default]

### FR-02 Services Display [CONFIRMED]
- Admin can manage a list of services (title, description, icon/image).
- Each service can be shown/hidden on the public site.

### FR-03 Vessel Trading — Public Listing [CONFIRMED]
- Public visitors can browse a list of available vessel opportunities.
- Each vessel listing shows **General Information only** (see §16).
- Each listing displays up to **6 images**. [CONFIRMED]
- Each listing has a visible **Status**: `Open` or `Closed`. [CONFIRMED]
- `Closed` listings remain visible (greyed out / marked closed) but do not accept new inquiries. [RECOMMENDATION]
- Vessel details page is required. [CONFIRMED]
- Filtering / search by vessel type, status, and key spec is included. [RECOMMENDATION]

### FR-04 Inquiry — Per Vessel [CONFIRMED]
Each vessel listing must offer three inquiry channels:
1. **WhatsApp** — click-to-chat with a pre-filled message referencing the vessel.
2. **Email** — mailto link with subject pre-filled referencing the vessel.
3. **Inquiry Form** — name, contact, message, tied to the specific vessel.

### FR-05 General Contact [CONFIRMED]
- A general contact section/page reachable from every page footer/header.
- WhatsApp + Email + Form (general, not vessel-specific). [RECOMMENDATION]

### FR-06 Gallery [CONFIRMED]
- Admin can manage a gallery (images, optional captions, categories). [RECOMMENDATION: categories]
- Gallery is visible publicly.

### FR-07 CMS — Authentication [CONFIRMED]
- Admin login secured by username/email + password.
- Single admin role for MVP. [CONFIRMED]
- Additional roles (editor, broker-only) are [FUTURE].

### FR-08 CMS — Vessel Listing Management [CONFIRMED]
- Admin can create, read, update, delete, and toggle status of vessel listings.
- Admin can upload up to 6 images per listing, with ordering. [CONFIRMED]
- Admin can mark a listing `Open` / `Closed`. [CONFIRMED]
- Sensitive fields (vessel name, IMO, owner, certificates) are stored internally but **never** rendered on the public site. [CONFIRMED]

### FR-09 CMS — Inquiry Inbox [CONFIRMED]
- Admin can view inquiries submitted via the form.
- Each inquiry shows: name, contact, message, associated vessel (if any), submitted-at.
- Admin can mark inquiry as read / contacted / archived. [RECOMMENDATION]
- Email notification to a configured inbox on new inquiry submission. [RECOMMENDATION]
- WhatsApp/Email click-events are tracked via analytics, not stored as records. [RECOMMENDATION]

### FR-10 SEO & Sharing [RECOMMENDATION]
- SEO-friendly URLs for all public pages.
- Open Graph + meta description per page and per vessel listing.
- Sitemap.xml auto-generated.

### FR-11 Performance & Responsive [CONFIRMED]
- Fully responsive: mobile, tablet, desktop.
- Mobile-first design given the target audience behavior.

---

## 7. Non-Functional Requirements

| Area | Requirement | Type |
|------|-------------|------|
| Performance | LCP < 2.5s on desktop, < 3s on 4G mobile; images optimized & lazy-loaded. | [RECOMMENDATION] |
| Availability | Uptime ≥ 99% (shared hosting acceptable for MVP). | [RECOMMENDATION] |
| Security — Public | No sensitive vessel data exposed in HTML, JS, network response, or alt text. | [CONFIRMED] |
| Security — Admin | HTTPS enforced; admin route protected by auth; password hashing; rate-limited login. | [CONFIRMED] |
| Security — Form | CSRF protection, basic spam protection (honeypot / rate limit) on inquiry form. | [RECOMMENDATION] |
| Privacy | Inquiry form includes consent checkbox for data use; no third-party data sharing. | [RECOMMENDATION] |
| Accessibility | Follow WCAG 2.1 AA basics: alt text, sufficient contrast, keyboard-navigable menus. | [RECOMMENDATION] |
| Browsers | Latest 2 versions of Chrome, Safari, Edge, Firefox. | [CONFIRMED] |
| Localization | Indonesian default; English toggle if approved. | ID [CONFIRMED] / EN [RECOMMENDATION] |
| Analytics | GA4 installed; conversion events on inquiry CTAs. | [RECOMMENDATION] |
| Maintainability | CMS-driven content; no code deploy needed to update copy/vessels/gallery. | [CONFIRMED] |
| Backup | DB + uploaded media backed up at least weekly. | [RECOMMENDATION] |

---

## 8. Feature Scope

### 8.1 In Scope (MVP)
**[CONFIRMED]** — all items below are committed to the MVP:

1. Public Company Profile pages (About, Overview, Vision & Mission).
2. Services section (CMS-managed).
3. Vessel Trading public listing & detail pages (general info, up to 6 images, Open/Closed status).
4. Inquiry channels per vessel (WhatsApp, Email, Inquiry Form).
5. General contact (WhatsApp + Email + Form). [RECOMMENDATION level — included because tied to KPIs]
6. Gallery (CMS-managed).
7. CMS authentication (single admin).
8. CMS for Company Profile content.
9. CMS for Services.
10. CMS for Vessel Listings (with sensitive-data protection).
11. CMS for Gallery.
12. CMS Inquiry Inbox (form submissions).
13. Responsive design — mobile-first.

### 8.2 Out of Scope (NOT in this MVP)
**[FUTURE]** — must NOT be built in this phase:

1. CRM / Lead Pipeline / Deal stages.
2. Commission calculation or brokerage accounting.
3. AIS / real-time vessel tracking.
4. Customer accounts or login on public site.
5. Online payment / invoicing.
6. Vessel owner self-service portal (owners submitting listings themselves).
7. Multi-language beyond the recommended ID/EN toggle.
8. Advanced search/filter beyond basic type/status/spec.
9. Email marketing automation / newsletters.
10. Inquiry auto-reply sequences beyond a single confirmation email. [RECOMMENDATION: simple auto-reply only]
11. Operational features (voyage, shipment, KPI dashboard) — those belong to the operational system already described in `docs/PRD.md`.
12. Mobile native apps.
13. Multiple business unit storefronts (only Jaya Sakti Line in this MVP).

---

## 9. In Scope — Detailed Acceptance Mapping

> This section exists to remove ambiguity between "what is required" vs "what is nice to have".

| Feature | Confirmed deliverable | Recommendation (pending approval) |
|---------|----------------------|-----------------------------------|
| Company Profile | About, Overview, Vision & Mission pages | ID/EN toggle |
| Services | CRUD services, show/hide | Icon/image per service |
| Vessels | List + detail, 6 images, Open/Closed | Filter/search, Related vessels |
| Inquiry | WA + Email + Form per vessel | General contact page, Auto-reply email |
| Gallery | CRUD images | Categories, captions |
| CMS | Single admin login, CRUD all content | Roles, audit log |
| Inquiry Inbox | View submissions per vessel | Status flags, email notification |
| Performance | Responsive, mobile-first | LCP targets, lazy loading |
| SEO | Clean URLs | OG tags, sitemap.xml, GA4 |

---

## 10. Sitemap

```
Home
├── About
│   ├── Company Overview
│   └── Vision & Mission
├── Services
├── Vessel Trading
│   ├── Listing (index of vessels)
│   └── Vessel Detail (per vessel)
├── Gallery
├── Contact (general)
└── (Footer: links to all above + WhatsApp + Email)

Admin (CMS) — not public
├── Login
├── Dashboard
├── Company Profile editor
├── Services manager
├── Vessel Listings manager
│   ├── Create / Edit / Delete
│   └── Toggle Open/Closed
├── Gallery manager
└── Inquiries inbox
```

---

## 11. Public User Flow

### Flow A — Browse & Inquire (most common)
1. Visitor lands on Home.
2. Sees overview of company, services, featured vessel(s).
3. Clicks **Vessel Trading** → sees listing index with thumbnails + status badges.
4. Clicks a vessel → detail page (general info, images, status).
5. Clicks **WhatsApp / Email / Inquiry Form** → inquiry sent referencing that vessel.
6. Sees confirmation message.

### Flow B — Brand Credibility Check
1. Visitor arrives (e.g., from a business card or referral).
2. Opens **About** → reads Company Overview + Vision & Mission.
3. Opens **Services** → understands capability.
4. Uses **Contact** to reach out generally.

### Flow C — Vessel Owner Prospecting
1. Vessel owner hears about Jaya Sakti Line as a broker.
2. Reads About + Services to assess credibility.
3. Uses general **Contact** (email/form) to propose listing their vessel.

---

## 12. Admin Flow

### Flow D — Manage Vessel Listing
1. Admin logs in.
2. Opens Vessel Listings manager.
3. Creates new listing → fills general info, uploads ≤ 6 images, sets status.
   - Sensitive data (vessel name, IMO, owner, certificates) entered into internal-only fields. [RECOMMENDATION: capture but never display]
4. Saves → listing appears publicly (if status = Open).
5. Later toggles status Open ↔ Closed as needed.

### Flow E — Handle Inquiry
1. Admin receives email notification of new inquiry. [RECOMMENDATION]
2. Opens Inquiry Inbox → sees inquiry details + linked vessel.
3. Marks inquiry as contacted / archived. [RECOMMENDATION]
4. Follows up with the lead via their preferred channel (WA/email/phone).

### Flow F — Update Company Content
1. Admin opens Company Profile editor.
2. Edits About / Overview / Vision & Mission copy.
3. Saves → changes go live immediately.

### Flow G — Manage Gallery
1. Admin opens Gallery manager.
2. Adds/removes/reorders images.
3. Saves → gallery updates publicly.

---

## 13. CMS Requirements

| Requirement | Type |
|-------------|------|
| Secure login (HTTPS, hashed password, throttled). | [CONFIRMED] |
| Single admin role for MVP. | [CONFIRMED] |
| Dashboard landing page showing quick counts (listings, inquiries). | [RECOMMENDATION] |
| CRUD for Company Profile content (rich text). | [CONFIRMED] |
| CRUD for Services. | [CONFIRMED] |
| CRUD for Vessel Listings, incl. image upload (max 6) + ordering. | [CONFIRMED] |
| Toggle Vessel Status Open/Closed. | [CONFIRMED] |
| CRUD for Gallery. | [CONFIRMED] |
| Inquiry inbox (list, view, search/filter). | [CONFIRMED] |
| Mark inquiry read/contacted/archived. | [RECOMMENDATION] |
| Activity/audit log of admin actions. | [RECOMMENDATION] |
| Soft-delete for vessels & inquiries (recoverable). | [RECOMMENDATION] |
| Image optimization on upload (resize/compress). | [RECOMMENDATION] |

---

## 14. Content Management Requirements

### 14.1 Company Profile
- Fields: About (long text), Company Overview (long text), Vision (text), Mission (text/list).
- Rich-text editor supporting headings, bold, lists, images. [RECOMMENDATION]
- All editable from CMS, no code deploy. [CONFIRMED]

### 14.2 Services
- Each service: Title, Short description, Optional icon/image, Optional "show on home" flag. [RECOMMENDATION: flag]
- Orderable. [RECOMMENDATION]

### 14.3 Gallery
- Fields: Image, Optional caption, Optional category. [RECOMMENDATION: category]
- Orderable. [RECOMMENDATION]

### 14.4 Vessel Content — see §16.

### 14.5 Media handling
- Max image size defined (e.g., 5MB). [RECOMMENDATION]
- Auto-resize to web-optimized dimensions on upload. [RECOMMENDATION]
- Stored in private storage for admin; public storage with obfuscated paths for public-viewable images. [RECOMMENDATION]

---

## 15. Vessel Trading Requirements

### 15.1 Publicly Visible Vessel Fields [CONFIRMED — General Information]
Each vessel listing publicly displays **only** the following general information:

- Vessel reference code / Listing ID (not the real vessel name) [RECOMMENDATION — to give each listing a stable public handle]
- Vessel type (e.g., Bulk Carrier, Tugboat, Barge)
- Year built
- Flag / registry (general, not owner-identifying) [RECOMMENDATION]
- Gross tonnage / Deadweight (general spec)
- Main dimensions (LOA, beam, draft) [RECOMMENDATION]
- Engine / power (general) [RECOMMENDATION]
- Trading area / location (general, non-sensitive) [RECOMMENDATION]
- Brief marketing description
- Up to 6 photos
- Status: Open / Closed
- Inquiry CTAs (WhatsApp / Email / Form)

### 15.2 Sensitive Fields — NEVER Public [CONFIRMED]
The following must **never** appear in any public page, API response, HTML source, alt text, or downloadable asset:

- Vessel Name (real)
- IMO Number
- Owner / ownership details
- Full certificates
- Price / commercial terms [RECOMMENDATION to also treat as sensitive]

These fields may be captured in the CMS for internal use (e.g., broker reference), but only the **broker/admin** sees them.

### 15.3 Listing Lifecycle
- New listing created → defaults to `Open` (admin can choose `Closed` initially). [CONFIRMED default Open]
- `Open`: visible publicly, inquiries accepted.
- `Closed`: visible publicly (marked closed), inquiries disabled. [RECOMMENDATION] — alternative: hide closed entirely. [To confirm with stakeholders]
- Admin can delete listing (soft-delete recommended). [RECOMMENDATION]

### 15.4 Images
- Up to 6 images per listing. [CONFIRMED]
- Admin-controlled ordering. [CONFIRMED]
- First image used as listing thumbnail. [RECOMMENDATION]
- Images must not contain EXIF/metadata that leaks owner/owner name/IMO. [RECOMMENDATION — strip on upload]

---

## 16. Inquiry Requirements

### 16.1 Channels [CONFIRMED]
Each vessel listing exposes:
1. **WhatsApp** — `wa.me` link with prefilled message:
   `Hello Jaya Sakti Line, I'm interested in listing <ListingID>.`
2. **Email** — `mailto:` with subject: `Inquiry: Vessel <ListingID>`.
3. **Inquiry Form** — fields:
   - Name (required)
   - Company (optional) [RECOMMENDATION]
   - Email or Phone (at least one required)
   - Message (required)
   - Vessel reference (auto-attached, hidden)
   - Consent checkbox (data use) [RECOMMENDATION]

### 16.2 General Contact
- Same three channels but not tied to a vessel. [RECOMMENDATION]

### 16.3 Routing & Notification
- Form submissions stored in CMS inquiry inbox. [CONFIRMED]
- Email notification to configured broker inbox on new submission. [RECOMMENDATION]
- Simple auto-reply confirmation to the submitter. [RECOMMENDATION]
- WhatsApp/Email clicks are tracked as conversion events only — no record stored. [RECOMMENDATION]

### 16.4 Data Retention
- Inquiry records retained ≥ 12 months for audit. [RECOMMENDATION]
- Personal data of inquirers not shared with third parties. [RECOMMENDATION]

---

## 17. Acceptance Criteria

The MVP is accepted when ALL of the following are true:

### Public Site
- [AC-1] Home, About (Overview + Vision & Mission), Services, Vessel Trading, Gallery, Contact pages render correctly on mobile, tablet, desktop. [CONFIRMED]
- [AC-2] Vessel listing index shows vessels with thumbnail + status badge. [CONFIRMED]
- [AC-3] Vessel detail page shows ≤ 6 images, general information only, and 3 inquiry CTAs. [CONFIRMED]
- [AC-4] No sensitive data (vessel name, IMO, owner, certificates) appears anywhere in the public-facing HTML, network responses, images, or alt text. [CONFIRMED]
- [AC-5] `Open` vessels accept inquiries; `Closed` vessels are clearly marked. [CONFIRMED]
- [AC-6] WhatsApp link opens chat with prefilled message; Email opens mail client with prefilled subject. [CONFIRMED]
- [AC-7] Inquiry form validates required fields and shows success confirmation. [CONFIRMED]
- [AC-8] All pages < 3s LCP on 4G mobile test. [RECOMMENDATION]

### CMS
- [AC-9] Admin can log in and out securely. [CONFIRMED]
- [AC-10] Admin can CRUD Company Profile, Services, Gallery without code changes. [CONFIRMED]
- [AC-11] Admin can CRUD vessel listings, upload ≤ 6 images, reorder them, and toggle Open/Closed. [CONFIRMED]
- [AC-12] Admin can view inquiry submissions linked to vessels. [CONFIRMED]
- [AC-13] Admin can mark inquiries as read/contacted/archived. [RECOMMENDATION]

### NFR
- [AC-14] Site served over HTTPS; admin routes protected; login rate-limited. [CONFIRMED]
- [AC-15] GA4 installed and conversion events fire on inquiry CTAs. [RECOMMENDATION]
- [AC-16] Bilingual ID/EN toggle works if approved. [RECOMMENDATION]

---

## 18. Risks

| # | Risk | Impact | Mitigation | Type |
|---|------|--------|------------|------|
| R1 | Sensitive vessel data accidentally exposed publicly (vessel name, IMO, owner). | High | Strict server-side filtering of public payload; image EXIF stripping; QA checklist to verify HTML/network for sensitive fields. | [CONFIRMED mitigation] |
| R2 | Listings become stale → brand looks inactive. | Medium | Admin KPI: refresh listing status within 30 days; CMS dashboard highlights stale Open listings. | [RECOMMENDATION] |
| R3 | Inquiry spam floods inbox. | Medium | Honeypot + rate-limit + optional simple captcha; mark-as-spam feature. | [RECOMMENDATION] |
| R4 | WhatsApp/email click inquiries not measurable (only form is). | Medium | Tag WA/Email clicks as GA4 conversion events; report them alongside form submissions. | [RECOMMENDATION] |
| R5 | Scope creep into operational features (CRM, AIS, deals). | High | This PRD explicitly excludes them; any such request deferred to operational system / future roadmap. | [CONFIRMED boundary] |
| R6 | Image performance slows mobile experience. | Medium | Auto-resize on upload, lazy-load, WebP, responsive srcset. | [RECOMMENDATION] |
| R7 | Brand inconsistency if multiple business units added later. | Low | Architecture chosen should allow future multi-unit expansions without re-platforming. | [FUTURE concern] |

---

## 19. Future Roadmap (Out of MVP — for planning continuity only)

These items are **NOT** in MVP and must not be implemented in this phase.

### Phase 2 — Lead Intelligence [FUTURE]
- Inquiry-to-CRM sync (e.g., to HubSpot or the operational dashboard).
- Inquiry status pipeline (New → Contacted → Qualified → Lost/Won).
- Broker assignment & internal notes.

### Phase 3 — Vessel Owner Self-Service [FUTURE]
- Vessel owner accounts.
- Owner-submitted listing drafts (moderated by admin).
- Owner dashboard to view interest/inquiries on their vessels.

### Phase 4 — Marketplace Trust & Operations [FUTURE]
- Verified listings / broker certification badges.
- Saved favorites (registered buyers).
- Advanced search & comparison tool.

### Phase 5 — Multi-Business Platform [FUTURE]
- Host other Jaya Sakti business units (beyond Jaya Sakti Line) under one platform.
- Shared branding shell, per-unit subsite.

### Phase 6 — Operational Integrations [FUTURE]
- Integration with the operational logistics system defined in `docs/PRD.md` (only if/when business justifies).
- AIS tracking, voyage data — only if the public marketing story later requires it.

---

## 20. Development Recommendation

This section provides Product's view on **how** to deliver the MVP, without prescribing implementation details. Final tech decisions rest with Engineering.

### 20.1 Stack Recommendation
- The existing repo is a Laravel application (Filament available). For fastest delivery and consistency with the wider Jaya Sakti ecosystem, **reuse Laravel + Filament** for the CMS, and build the public website as Blade/Livewire or a thin front-end. [RECOMMENDATION]
- Keep public site and CMS as **one Laravel app** with separate route groups (`/` public, `/admin` protected). [RECOMMENDATION]
- Alternative: a separate lightweight site (e.g., static/Next.js) plus headless CMS. [RECOMMENDATION alternative] — only if marketing wants a non-Laravel front-end; otherwise default to the unified Laravel approach.

### 20.2 Data Model — Recommendation only (NOT a schema)
**Do not build migrations from this PRD.** The following is conceptual guidance for the engineering estimate:

- `CompanyProfile` (singleton-style content).
- `Service` (id, title, description, image_path, is_visible, sort_order).
- `VesselListing` (id, public_ref_code, type, year_built, general spec fields..., status, photos[], + internal-only sensitive fields: real_name, imo, owner, certificates).
- `VesselImage` (id, vessel_listing_id, path, sort_order).
- `GalleryItem` (id, path, caption, category, sort_order).
- `Inquiry` (id, name, company, email, phone, message, vessel_listing_id nullable, status, created_at).

### 20.3 Delivery Phasing (recommendation)
| Phase | Content | Type |
|-------|---------|------|
| Sprint 0 | Setup, design system, sitemap, CMS skeleton, auth. | [RECOMMENDATION] |
| Sprint 1 | Company Profile + Services + Gallery (public + CMS). | [RECOMMENDATION] |
| Sprint 2 | Vessel Trading (public listing + detail + CMS + images + status). | [RECOMMENDATION] |
| Sprint 3 | Inquiry (form + WA + Email + inbox + email notification). | [RECOMMENDATION] |
| Sprint 4 | Polish, SEO, GA4, responsive QA, security review for sensitive data. | [RECOMMENDATION] |

### 20.4 Hard Quality Gates before Launch [CONFIRMED]
1. **Sensitive data leakage test** — automated + manual check that vessel name/IMO/owner/certificates never appear in public HTML or any public-facing network response.
2. **Responsive QA** — sign-off on mobile, tablet, desktop.
3. **Security checklist** — HTTPS, auth on admin, CSRF, login throttle, form spam protection.
4. **Stakeholder content review** — Company Profile copy + at least 3 seed vessel listings approved by Jaya Sakti business owner.

### 20.5 Definition of Done (per feature)
A feature is "done" when:
- Deliverable meets its Acceptance Criteria (§17).
- Type-tag respected (no [FUTURE] items sneaked in).
- No sensitive data leaked.
- Works on mobile and desktop.
- Reviewed by Product before merge.

---

## 21. Open Questions for Stakeholder Workshop

These need business decisions before/while kicking off development. None should be assumed.

1. Should `Closed` vessel listings remain visible publicly (recommended) or be hidden entirely? [RECOMMENDATION: keep visible, marked closed]
2. Is bilingual ID/EN required for MVP, or is Indonesian only acceptable? [CONFIRMED: ID only unless EN approved]
3. Who is the broker email & WhatsApp number that inquiries route to?
4. Should form inquiries auto-reply to the submitter? [RECOMMENDATION: yes, simple confirmation]
5. Do we capture sensitive data (vessel name, IMO, owner, certificates) in the CMS for internal use, or not at all in MVP?
6. Is GA4 the agreed analytics tool, or another?
7. Acceptable max number of vessel listings at launch (seed content)?
8. Is the existing Laravel app the host for the public site, or do we want a separate front-end?
9. Domain name for the public site?

---

## Appendix A — Glossary
- **Vessel Trading** — brokering of vessels for sale/charter (not operational shipping).
- **Listing** — a public vessel opportunity posted by Jaya Sakti Line.
- **General Information** — non-sensitive public spec of a vessel.
- **Sensitive Information** — vessel name, IMO, owner, full certificates (never public).
- **Open / Closed** — lifecycle status of a listing.
- **CMS** — Content Management System for admins to manage site content.

## Appendix B — Related Documents
- `docs/01-business/` — Business Requirement Document (to be created, if required).
- `docs/03-architecture/ARD-001` — Architecture Design Document (next phase).
- `docs/PRD.md` — Operational Logistics & Distribution System PRD (separate product, do NOT mix scopes).
- `prd.md` — Concise execution PRD for the operational branch.

---

**End of PRD — Jaya Sakti Line Website MVP (PRD-001, v1.0.0, Frozen).**
This document is the single source of truth for the MVP. Any change in scope must be raised as a Change Request (CR) and re-approved by business stakeholders.
