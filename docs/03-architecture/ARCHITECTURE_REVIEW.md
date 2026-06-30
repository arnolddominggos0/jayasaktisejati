# Architecture Review
## PRD-001 — Jaya Sakti Line Website MVP
### Pre-ARD Architecture Decision Review

**Project:** Jaya Sakti App
**Document:** Architecture Review (precursor to ARD-001)
**Version:** 0.1.0 (Review Draft)
**Status:** 🔍 FOR ARCHITECTURE REVIEW BOARD
**Document Owner:** Architecture Team
**Last Updated:** 2026-06-30
**Next Phase:** ARD-001 — Architecture Design Document
**Input:** `docs/02-product/PRD-001-JSL-Website-MVP.md` (v1.0.0, Frozen)

> **Purpose**
> This document does **NOT** define the architecture. It identifies every architecture decision that must be finalized **before** ARD-001 is written and before implementation begins. For each decision it lists the question, available options, trade-offs, recommendation, reasoning, and risks.
>
> **Constraints carried from the project brief**
> - Stack is fixed: Laravel 11, PHP 8.3, Filament v3, MariaDB.
> - The website **will NOT** become a standalone application. It becomes part of the existing JayaSaktiApp ecosystem (the current `jss_dashboard` Laravel repo).
> - PRD-001 scope is frozen. No business/feature discussion here — architecture only.

---

## Change History

| Version | Date | Author | Description |
|---------|------|--------|-------------|
| 0.1.0 | 2026-06-30 | Architecture Team | Initial Architecture Review draft for ARB. |

---

## 0. Context Snapshot from the Existing Ecosystem

These facts were verified in the current `jss_dashboard` repo and constrain the decisions below:

1. **One Laravel 11 app** already serves an operational logistics system (`docs/PRD.md`).
2. **Three Filament panels** already exist: `admin` (`/admin`), `field-coordinator`, and a `customer` panel. The `admin` panel uses `EnsurePanelRole` + `ScopeByBranch` middleware and is branch-scoped.
3. **Auth/authorization stack present:** Laravel Sanctum, Spatie Laravel Permission, Spatie Laravel Activity Log.
4. **Model collision risk:** `App\Models\Vessel` already exists (operational). The PRD's marketing concept is a different thing ("vessel listing/opportunity").
5. **Public web routes already exist** in `routes/web.php` (landing, tracking) under `App\Http\Controllers\Public`.
6. **Filesystems configured:** `local`, `public` (with `storage:link`), `s3`.
7. **Operational system is multi-branch scoped.** The marketing website is single-tenant and public — a boundary tension that must be explicitly resolved.

> These facts are stated for accuracy. They are **not** implementation instructions.

---

## 1. Overall System Architecture

### Decision AR-01 — Deployment topology of the website relative to the existing app
- **Question:** Does the Jaya Sakti Line website run inside the existing `jss_dashboard` Laravel process, or as a separate runtime that shares the database/codebase?
- **Why it matters:** The brief states the website "will NOT become a standalone application" and becomes part of the ecosystem. The topology determines release coupling, resource sharing, and blast radius.
- **Available Options:**
  - **A. Monolith inside the existing app** — website is a public-facing route group + module within the same Laravel process that runs the operational system.
  - **B. Same codebase, separate process/worker pool** — shared codebase and DB, but the public site served by a different web root / worker config.
  - **C. Separate app, shared DB** — a second Laravel app reusing the MariaDB instance.
- **Pros / Cons:**
  - **A:** Pros — single deploy, maximal reuse of auth/media/config, lowest MVP cost, consistent with the brief. Cons — public traffic shares resources with operational admin; a public spike can affect internal users.
  - **B:** Pros — isolates public traffic resource pressure; still one codebase. Cons — more operational complexity (two web roots, two deploy targets) for little MVP gain.
  - **C:** Pros — strongest isolation. Cons — violates "not standalone"; code drift risk; doubles maintenance.
- **Recommendation:** **A.**
- **Reasoning:** The brief explicitly excludes a standalone app. MVP traffic is low (KPI: ≥ 500 listing views/month). The cost of B/C is not justified for MVP. Resource isolation concerns are better addressed by hosting/scaling (§17) than by splitting the codebase.
- **Risks:** Public traffic spikes could degrade operational admin performance — mitigated by caching (§17) and hosting choice, not by codebase splitting.

---

## 2. Application Boundary

### Decision AR-02 — Logical boundary between marketing website and operational system
- **Question:** How is the marketing website kept logically separated from the operational logistics system while living in the same app?
- **Why it matters:** PRD §8.2 explicitly excludes operational features (voyage, shipment, KPI, CRM). Without a hard boundary, scope creep (Risk R5) is inevitable.
- **Available Options:**
  - **A. Module/namespace partition** — a dedicated module root for all website code (controllers, models, Filament resources), with a rule that the website module never imports operational services.
  - **B. Convention-only separation** — naming prefixes and folder conventions, no enforced boundary.
  - **C. Bounded context with anti-corruption layer** — strict isolation plus an ACL that translates any shared data.
- **Pros / Cons:**
  - **A:** Pros — enforceable in review/CI, clear ownership, cheap. Cons — requires discipline.
  - **B:** Pros — zero setup. Cons — erosion over time; scope-creep risk materializes.
  - **C:** Pros — strongest guarantee. Cons — over-engineered for an MVP with no shared operational data.
- **Recommendation:** **A.**
- **Reasoning:** The website shares **no operational data** in MVP (vessel listings are marketing entities, not the operational `Vessel`). A module partition with a "no operational imports" rule is sufficient and CI-checkable.
- **Risks:** Without enforcement, developers reuse operational models out of convenience. Mitigation: a static review rule / CI check that the website module does not depend on operational namespaces.

---

## 3. Module Architecture

### Decision AR-03 — Internal structure of the website module
- **Question:** How is the website module internally organized for maintainability and future multi-business expansion (PRD Phase 5)?
- **Why it matters:** PRD BO-6 and Phase 5 anticipate other Jaya Sakti business units on the same platform. Structure now determines whether that future is cheap or requires re-platforming (Risk R7).
- **Available Options:**
  - **A. Single flat "Website" module** — one module containing all website features.
  - **B. Business-unit-scoped module** — structure the website under a "Jaya Sakti Line" unit namespace from day one, even though only one unit exists.
  - **C. Modular monolith with per-unit modules + a shared platform kernel** — explicit shared kernel (auth, media, navigation) and per-unit feature modules.
- **Pros / Cons:**
  - **A:** Pros — simplest, fastest MVP. Cons — Phase 5 forces a restructure.
  - **B:** Pros — cheap option to keep the "Line" brand isolated; Phase 5 adds siblings, not a rewrite. Cons — slight upfront structure cost.
  - **C:** Pros — cleanest long-term. Cons — speculative abstraction for an MVP with one unit.
- **Recommendation:** **B.**
- **Reasoning:** B captures 80% of C's future benefit at A's near-cost. It signals intent without over-abstracting. The shared kernel in C can emerge when a second unit is actually approved.
- **Risks:** If B is chosen poorly (leaking shared concepts into the Line namespace), Phase 5 still hurts. Mitigation: ARD-001 must define what belongs to "the Line unit" vs "the platform."

---

## 4. Public Website Architecture

### Decision AR-04 — Public site rendering strategy
- **Question:** How are public pages rendered: server-side Blade, Livewire, or a JS SPA?
- **Why it matters:** PRD targets are mobile-first, LCP < 3s on 4G, SEO-friendly (FR-10), and low-bandwidth IMIP/Morowali users (Persona A).
- **Available Options:**
  - **A. Server-side Blade** — traditional full-page server rendering.
  - **B. Livewire components** — interactive components on top of Blade.
  - **C. SPA (Inertia or separate JS front-end).**
- **Pros / Cons:**
  - **A:** Pros — best LCP, best SEO, smallest payload, simplest caching, fits low-bandwidth users. Cons — interactivity requires full page transitions (acceptable for a brochureware + listing site).
  - **B:** Pros — easy interactivity (e.g., vessel filter). Cons — Livewire round-trips can hurt LCP/perceived performance on 4G if overused.
  - **C:** Pros — rich UX. Cons — worst LCP, worst SEO effort, largest payload, contradicts mobile-first low-bandwidth persona.
- **Recommendation:** **A (Blade) as default**, with Livewire used **only** where interactivity is genuinely needed (e.g., vessel filter/search).
- **Reasoning:** The site is predominantly static-content + listing + inquiry — a textbook server-rendered fit. Persona A is on 4G mobile with low patience. SEO is a PRD goal.
- **Risks:** Over-using Livewire for marketing pages reintroduces round-trip latency. Mitigation: ARD-001 sets a rule — Livewire only for stateful interactive components, never for static content.

---

## 5. CMS Architecture

### Decision AR-05 — CMS delivery mechanism
- **Question:** Is the CMS a new Filament panel, a fold-in to the existing `admin` panel, or non-Filament?
- **Why it matters:** The repo already has 3 Filament panels. The `admin` panel is branch-scoped and operational. The CMS is single-tenant marketing content. Mixing them couples unrelated concerns and breaks the boundary (AR-02).
- **Available Options:**
  - **A. Dedicated new Filament panel** (e.g., a "website admin" panel at its own path).
  - **B. Fold CMS resources into the existing `admin` panel.**
  - **C. Custom non-Filament admin area.**
- **Pros / Cons:**
  - **A:** Pros — clean boundary, no `ScopeByBranch` interference, separate auth/role gate, independent navigation, consistent Filament UX for admins. Cons — one more panel to maintain.
  - **B:** Pros — no extra panel. Cons — forces branch-scoping middleware onto non-branch content; navigation clutter; boundary erosion.
  - **C:** Pros — fully tailored. Cons — rebuilds CRUD/upload/table UX that Filament already provides; highest cost.
- **Recommendation:** **A.**
- **Reasoning:** The existing `admin` panel's `ScopeByBranch` + `EnsurePanelRole` are operational concerns that do not apply to marketing content. A dedicated panel preserves the boundary (AR-02) and reuses Filament's CRUD/upload strengths without operational coupling.
- **Risks:** Admin users may need to switch between panels. Mitigation: acceptable for MVP; single admin role (FR-07) reduces friction.

---

## 6. Domain Architecture

### Decision AR-06 — Naming/identity of the marketing vessel entity vs the operational `Vessel`
- **Question:** How do we name and isolate the marketing "vessel listing" concept given `App\Models\Vessel` already exists for operations?
- **Why it matters:** PRD §15 defines a marketing listing (general info + sensitive internal fields, Open/Closed). The operational `Vessel` is a different domain object. Reusing it would leak operational fields and break the boundary (AR-02).
- **Available Options:**
  - **A. Distinct marketing entity** with its own name (e.g., a `VesselListing` concept) in the website module, **no relationship** to operational `Vessel`.
  - **B. Extend/alias the operational `Vessel`.**
  - **C. Share one entity with a "type" discriminator.**
- **Pros / Cons:**
  - **A:** Pros — clean separation, sensitive-field isolation is straightforward, no operational coupling. Cons — two vessel concepts exist (acceptable; they are genuinely different domains).
  - **B:** Pros — single model. Cons — severe boundary erosion, sensitive-field leakage risk (AC-4), violates AR-02.
  - **C:** Pros — one table. Cons — single-table polymorphism across two bounded contexts; worst leakage risk.
- **Recommendation:** **A.**
- **Reasoning:** The marketing listing and the operational vessel are different bounded contexts with different lifecycle, fields, and audiences. A distinct entity makes AC-4 (no sensitive data on public pages) enforceable at the data-access layer.
- **Risks:** Stakeholders may later want the marketing listing to reference a real operational vessel. Mitigation: that is a **[FUTURE]** integration (PRD Phase 6), explicitly out of MVP.

---

## 7. Routing Strategy

### Decision AR-07 — Public route organization and URL structure
- **Question:** How are public routes registered and namespaced, and what URL structure do they use?
- **Why it matters:** PRD sitemap (§10) defines public pages; FR-10 asks for SEO-friendly URLs; the existing `routes/web.php` already has unrelated public routes (landing, tracking).
- **Available Options:**
  - **A. Dedicated website route file loaded into `web.php`**, with clean SEO URLs (`/vessels`, `/vessels/{ref}`, `/about`, `/services`).
  - **B. Append all website routes directly into the existing `web.php`.**
  - **C. Route group under a `/line/` prefix.**
- **Pros / Cons:**
  - **A:** Pros — clean URLs, maintainable, separation from operational routes, good SEO. Cons — needs a route-loading convention.
  - **B:** Pros — zero setup. Cons — `web.php` becomes cluttered; route name collision risk.
  - **C:** Pros — namespace isolation. Cons — uglier URLs (`/line/vessels`), worse SEO and branding.
- **Recommendation:** **A.**
- **Reasoning:** SEO-friendly root-level URLs serve PRD BO-1/BO-3 (branding, marketing). A dedicated route file keeps the boundary (AR-02) clean without prefixing URLs.
- **Risks:** Route name collisions with existing routes (e.g., `landing`, `tracking`). Mitigation: ARD-001 defines a route-name prefix convention for the website module.

---

## 8. Navigation Strategy

### Decision AR-08 — Public navigation model (header/footer)
- **Question:** Is the public navigation CMS-driven or code/template-driven?
- **Why it matters:** PRD sitemap is fixed for MVP, but CMS-driven content (FR-01/FR-02) might tempt teams to also CMS-drive navigation. Over-building navigation CMS adds cost; under-building locks copy changes behind deploys.
- **Available Options:**
  - **A. Template-driven navigation** with fixed menu items pointing to CMS-managed pages.
  - **B. Fully CMS-driven navigation** (admin can add/reorder menu items).
  - **C. Hybrid** — fixed structure, CMS-driven labels/visibility.
- **Pros / Cons:**
  - **A:** Pros — simplest, stable, predictable SEO. Cons — menu changes need a deploy.
  - **B:** Pros — total flexibility. Cons — over-built for MVP; risk of broken nav/SEO.
  - **C:** Pros — labels editable without deploy; structure stable. Cons — slightly more config.
- **Recommendation:** **A** for MVP.
- **Reasoning:** The sitemap is fixed and small. Navigation structure won't change in MVP. Page *content* is CMS-driven (FR-01/FR-02), which is what matters. Flexibility can be a [FUTURE] enhancement.
- **Risks:** Marketing later wants to add a nav item without IT. Mitigation: low-cost [FUTURE] enhancement; not a reason to over-build now.

---

## 9. Shared Components

### Decision AR-09 — Component sharing between website and operational UI
- **Question:** Which components, if any, are shared between the public website and the operational/CMS UI?
- **Why it matters:** Reusing operational components risks leakage (visual or data) into public pages; duplicating everything inflates cost.
- **Available Options:**
  - **A. Website has its own component set; share only generic primitives (e.g., image, SEO head).**
  - **B. Freely reuse operational components.**
  - **C. Share everything via a common UI kit.**
- **Pros / Cons:**
  - **A:** Pros — controlled brand separation, no operational leakage, clear ownership. Cons — minor duplication of generic primitives.
  - **B:** Pros — less code. Cons — brand/visual leakage; coupling; boundary erosion (AR-02).
  - **C:** Pros — consistency. Cons — premature design-system investment for one unit.
- **Recommendation:** **A.**
- **Reasoning:** The public site has its own brand/UX (marketing) distinct from the operational admin. Only stateless primitives (image rendering, meta tags) are worth sharing.
- **Risks:** Divergent image handling between modules. Mitigation: ARD-001 designates one media primitive (see AR-13).

---

## 10. Authentication Boundary

### Decision AR-10 — CMS admin authentication vs operational auth
- **Question:** Does the CMS reuse the existing `web` guard / user table, or have its own auth surface?
- **Why it matters:** The existing `admin` panel uses the `web` guard with `EnsurePanelRole` + `ScopeByBranch`. The CMS admin is a marketing user, not an operational branch user. PRD FR-07 mandates a single admin role.
- **Available Options:**
  - **A. Reuse the existing users table + `web` guard, gated by a dedicated CMS panel role** (via Spatie Permission), **without** branch scoping.
  - **B. Separate guard and separate users table for CMS.**
  - **C. Reuse the operational `admin` panel auth wholesale.**
- **Pros / Cons:**
  - **A:** Pros — one identity store, reuses Spatie Permission, minimal new infra; CMS panel excludes branch middleware. Cons — must ensure CMS role is not branch-scoped.
  - **B:** Pros — total isolation. Cons — duplicate auth surface, password management twice; over-built for one admin.
  - **C:** Pros — zero config. Cons — drags `ScopeByBranch` into marketing content; boundary violation.
- **Recommendation:** **A.**
- **Reasoning:** One user store with a dedicated CMS role is the cheapest correct option. The boundary is enforced by the **panel's middleware stack** (no `ScopeByBranch`), not by a second guard.
- **Risks:** A user with both operational and CMS roles could get confusing scoping. Mitigation: ARD-001 specifies panel-role exclusivity or explicit role-per-panel mapping (extends `EnsurePanelRole`).

---

## 11. Authorization Boundary

### Decision AR-11 — Authorization model for the CMS
- **Question:** How is authorization modeled given PRD's single-admin MVP but a [FUTURE] multi-role roadmap (editor, broker-only)?
- **Why it matters:** PRD FR-07: single admin role for MVP. PRD §19 Phase 2/3: broker assignment, roles. Over-building RBAC now wastes effort; under-building forces a rewrite later.
- **Available Options:**
  - **A. One CMS role now via Spatie Permission; design resource-level policies so granular permissions can be added later without restructure.**
  - **B. Build full RBAC matrix now (admin, editor, broker).**
  - **C. Hard-code a single-admin check, no permission framework.**
- **Pros / Cons:**
  - **A:** Pros — MVP-correct, cheap, future-safe (permissions are granular from day one). Cons — requires discipline to use policies, not inline checks.
  - **B:** Pros — ready for future. Cons — speculative; violates "no future scope."
  - **C:** Pros — quickest. Cons — throws away the Spatie Permission infra already present; painful later.
- **Recommendation:** **A.**
- **Reasoning:** Spatie Permission is already installed. Using it with one role and resource policies is near-free and makes Phase 2 roles an additive change, not a rewrite.
- **Risks:** Inline `if (admin)` checks bypass policies. Mitigation: ARD-001 mandates policy-based authorization; reviewed in CI.

---

## 12. Security Boundary

### Decision AR-12 — Sensitive vessel data isolation strategy
- **Question:** At which layer(s) is sensitive data (vessel name, IMO, owner, certificates) isolated from the public surface?
- **Why it matters:** PRD AC-4 is a hard acceptance criterion: sensitive data must never appear in public HTML, network responses, images, or alt text. Risk R1 is High impact.
- **Available Options:**
  - **A. Defense in depth** — sensitive fields never leave a dedicated internal storage/context; public rendering uses a dedicated "public" projection (a read shape that physically excludes sensitive fields); image EXIF stripping on upload; QA gate (PRD §20.4).
  - **B. Single-layer filtering** — one transformer strips sensitive fields before rendering.
  - **C. Field-level flags + conditional Blade rendering.**
- **Pros / Cons:**
  - **A:** Pros — multiple independent controls; a failure in one layer does not leak. Cons — slightly more structure.
  - **B:** Pros — simplest. Cons — single point of failure; one missed endpoint leaks data.
  - **C:** Pros — flexible. Cons — extremely fragile; easy to forget a field in a template.
- **Recommendation:** **A.**
- **Reasoning:** AC-4 is non-negotiable and the data is commercially sensitive. A "public projection" approach means public endpoints literally cannot select sensitive columns. EXIF stripping protects image metadata. The QA gate is the final net.
- **Risks:** A developer bypasses the projection "just this once." Mitigation: ARD-001 forbids direct model-to-view passing for vessel listings; review checklist item; covered by the PRD hard gate.

---

## 13. Media Management

### Decision AR-13 — Media storage, optimization, and serving strategy
- **Question:** How are vessel/gallery images stored, optimized, and served publicly?
- **Why it matters:** PRD §15.4 (≤6 images/listing, ordering, EXIF strip), §14.5 (resize, obfuscated public paths, private admin storage), FR-11 (mobile-first), NFR performance. Risk R6 (image performance).
- **Available Options:**
  - **A. Local `public` disk with `storage:link`, responsive variants generated on upload, EXIF stripped, obfuscated filenames; a media library/intervention pipeline on upload.**
  - **B. S3/CDN-served media.**
  - **C. Plain uploads, no processing.**
- **Pros / Cons:**
  - **A:** Pros — fits shared-hosting MVP, no external cost, responsive images for 4G, EXIF safety. Cons — local disk scaling limits (acceptable for MVP volume).
  - **B:** Pros — best scaling/perf. Cons — cost + config for low MVP volume; external dependency.
  - **C:** Pros — zero effort. Cons — fails LCP (R6), fails EXIF/privacy (R1), bandwidth-heavy for 4G.
- **Recommendation:** **A** for MVP, with the media abstraction written so S3/CDN (B) is a config swap later.
- **Reasoning:** MVP volume is low; local disk with generated responsive variants + EXIF stripping satisfies AC-8, AC-4, and R6. Keeping the disk configurable preserves the S3 path without building it now.
- **Risks:** Local disk fills or loses media on server migration. Mitigation: weekly backup (PRD NFR) and a documented migration path to S3.

---

## 14. Content Management

### Decision AR-14 — Rich-content storage and editing approach
- **Question:** How is rich content (About, Overview, Vision, Mission, service descriptions) stored and edited?
- **Why it matters:** PRD §14.1 recommends a rich-text editor (headings, bold, lists, images). Rich text introduces XSS risk and storage format decisions.
- **Available Options:**
  - **A. Structured fields (per-section text blocks) + a sanitized WYSIWYG, stored as clean HTML, output via an output-escaping layer that allows a safe tag whitelist.**
  - **B. Markdown storage + render-time HTML.**
  - **C. Raw HTML, no sanitization.**
  - **D. Page-builder / blocks system.**
- **Pros / Cons:**
  - **A:** Pros — non-technical admin friendly, controlled XSS surface. Cons — must maintain a sanitizer allow-list.
  - **B:** Pros — safest, portable. Cons — less friendly for non-technical admins (Persona C).
  - **C:** Pros — easiest. Cons — unacceptable XSS risk.
  - **D:** Pros — ultimate flexibility. Cons — over-built for MVP; [FUTURE].
- **Recommendation:** **A.**
- **Reasoning:** Persona C is non-technical and needs WYSIWYG. Stored HTML with a strict allow-list (purified on save and on render) balances usability and safety.
- **Risks:** XSS via a missed vector. Mitigation: sanitize on **both** save and render; allow-list, never block-list.

---

## 15. Inquiry Flow

### Decision AR-15 — Inquiry submission, notification, and tracking architecture
- **Question:** How are the three inquiry channels (WhatsApp, Email, Form) handled, notified, and measured?
- **Why it matters:** PRD FR-04/FR-09/§16. Form submissions are persisted; WA/Email are click-events only; email notification to broker is recommended; auto-reply is recommended; GA4 conversion events are recommended (Risk R4).
- **Available Options:**
  - **A. Form → persisted inquiry + queued email (broker notification + submitter auto-reply) + GA4 event on all three CTAs; WA/Email are client-side links with analytics events, no server record.**
  - **B. Persist all three channels server-side.**
  - **C. Form-only, no notifications.**
- **Pros / Cons:**
  - **A:** Pros — matches PRD exactly, measurable, reliable, cheap. Cons — WA/Email conversions depend on client-side analytics firing.
  - **B:** Pros — full audit. Cons — violates PRD (WA/Email are not stored) and adds complexity.
  - **C:** Pros — simplest. Cons — fails KPI measurement (R4) and broker responsiveness (Persona D).
- **Recommendation:** **A.**
- **Reasoning:** A is a literal mapping of PRD §16. Queued email prevents submission latency on the public form. GA4 events on WA/Email clicks recover the measurability lost by not storing them.
- **Risks:** WA/Email analytics events fail to fire (ad blockers). Mitigation: accept minor undercount; form submissions remain the authoritative KPI source.

---

## 16. Integration Strategy

### Decision AR-16 — Integration surface with the operational system and third parties
- **Question:** What does the website integrate with in MVP, and how is the integration boundary defined?
- **Why it matters:** PRD excludes operational integrations (§8.2 #11; Phase 6 [FUTURE]). Yet the website lives in the same app as the operational system. Third-party integrations exist (GA4, WhatsApp, email).
- **Available Options:**
  - **A. Zero operational integration in MVP; third-party integrations limited to email (SMTP), WhatsApp (wa.me links), and GA4 (tag). All isolated behind a thin integration port.**
  - **B. Read-only access to operational data for "featured" content.**
  - **C. Full bidirectional operational integration.**
- **Pros / Cons:**
  - **A:** Pros — respects frozen scope, minimal risk, clear future seam. Cons — none for MVP.
  - **B/C:** Cons — violates frozen PRD scope; rejected.
- **Recommendation:** **A.**
- **Reasoning:** The PRD is frozen and explicitly excludes operational integration. The architecture should make the *seam* explicit (a port/interface) so Phase 6 can add it without rewiring the website.
- **Risks:** Pressure to "just read one operational field." Mitigation: the website module's dependency rule (AR-02) forbids operational imports; CR process required for any exception.

---

## 17. Scalability Strategy

### Decision AR-17 — Caching and performance scaling for public pages
- **Question:** How do we meet LCP < 3s on 4G (AC-8) and protect the shared app from public traffic (AR-01 risk)?
- **Why it matters:** Public traffic shares the process with operational admin (AR-01). Vessel listings and content change infrequently — ideal for caching.
- **Available Options:**
  - **A. Full-page / response caching for public pages, cache invalidation on CMS update, HTTP cache headers, lazy-loaded responsive images.**
  - **B. No caching; rely on DB queries.**
  - **C. Reverse proxy / CDN in front of the whole app.**
- **Pros / Cons:**
  - **A:** Pros — meets AC-8, protects DB from public traffic, cheap. Cons — invalidation discipline required.
  - **B:** Pros — zero setup. Cons — fails AC-8 under load; endangers shared operational DB.
  - **C:** Pros — best perf + DDoS cushion. Cons — cost + config; possibly premature for MVP volume.
- **Recommendation:** **A**, with CDN (C) as an optional, config-ready enhancement if hosting supports it.
- **Reasoning:** Content is mostly static and CMS-driven; cache-on-publish with tag-based invalidation gives near-static performance from a dynamic app at MVP cost.
- **Risks:** Stale listings shown after a CMS update. Mitigation: ARD-001 defines cache tagging keyed to the entity that changed (listing/service/profile).

---

## 18. Deployment Strategy

### Decision AR-18 — Environments, build, and release coupling
- **Question:** What environments and release flow are needed, given the website shares a deploy with the operational system?
- **Why it matters:** Shared deploy (AR-01) means a website change ships through the same pipeline as operational changes — blast radius is shared.
- **Available Options:**
  - **A. Three environments (local/dev, staging, production); single shared deploy; feature-flag for website features; DB migrations gated by PRD hard gates.**
  - **B. Separate deploy pipeline for the website.**
  - **C. Direct-to-production.**
- **Pros / Cons:**
  - **A:** Pros — realistic, isolates unfinished work via flags, respects shared codebase. Cons — discipline required.
  - **B:** Cons — impossible with one codebase (AR-01).
  - **C:** Cons — unacceptable for a system already running operations.
- **Recommendation:** **A.**
- **Reasoning:** One codebase ⇒ one pipeline. Feature flags let marketing content/features ship dark without disrupting operations. Staging validates the sensitive-data gate (AC-4) before prod.
- **Risks:** A website deploy destabilizes operations. Mitigation: the website module's isolation (AR-02/AR-09) and feature flags reduce cross-impact; CI runs the existing test suite plus a website leak test.

---

## 19. Multi-Business Strategy

### Decision AR-19 — Foundation for future Jaya Sakti business units (PRD Phase 5)
- **Question:** What, if anything, do we build now to support multiple business units later — without violating "no future scope"?
- **Why it matters:** PRD BO-6 [RECOMMENDATION] and Phase 5 [FUTURE] anticipate sibling business units. Risk R7 warns against re-platforming later.
- **Available Options:**
  - **A. Build one unit now, but structure routes/namespace/branding so a second unit is additive (no shared table hacks, no hardcoded "Line" assumptions in the platform kernel).**
  - **B. Build a full multi-tenant substrate now.**
  - **C. Build with no regard to future units.**
- **Pros / Cons:**
  - **A:** Pros — MVP-correct, keeps Phase 5 cheap, no speculative abstraction. Cons — requires naming the "platform kernel" vs "Line unit" now.
  - **B:** Pros — future-ready. Cons — speculative multi-tenant complexity for one unit; violates "no future scope."
  - **C:** Pros — fastest. Cons — Phase 5 becomes a rewrite (R7).
- **Recommendation:** **A.**
- **Reasoning:** "Structural readiness without speculative features" is the architecturally correct reading of the frozen PRD. It costs almost nothing now and prevents R7.
- **Risks:** The line between "readiness" and "speculative feature" is subjective. Mitigation: ARD-001 lists exactly which structural choices are in-scope-for-readiness (namespacing, route file, brand config) and which are not (tenant selection, per-unit admin).

---

## 20. Future Extensibility

### Decision AR-20 — Extension points to leave open for the roadmap
- **Question:** Which explicit extension points does the architecture preserve for PRD §19 phases, without building them?
- **Why it matters:** Phases 2–6 (CRM, owner portal, trust features, multi-business, operational integration) are [FUTURE]. Architecture that accidentally closes these doors forces rewrites.
- **Available Options:**
  - **A. Define explicit, documented extension points: inquiry pipeline (for CRM sync), listing lifecycle (for owner-submitted drafts), media abstraction (for CDN), auth/roles (for broker accounts), integration port (for operational data).**
  - **B. Leave the future to the future.**
  - **C. Pre-build hooks for each phase.**
- **Pros / Cons:**
  - **A:** Pros — cheap insurance, documented intent, no over-build. Cons — must be honored during implementation.
  - **B:** Cons — high rewrite risk; doors silently close.
  - **C:** Cons — violates "no future scope"; speculative.
- **Recommendation:** **A.**
- **Reasoning:** Naming extension points is documentation, not implementation. It steers MVP code away from dead-ends (e.g., don't hardcode inquiry handling so CRM sync can hook in later).
- **Risks:** Extension points treated as features. Mitigation: each point is a one-line seam (interface/port or naming convention), not code.

---

## Additional Architecture Decisions (beyond the required 20)

A principal-architect review would be incomplete without these — they are real pre-implementation decisions.

### Decision AR-21 — Observability & monitoring
- **Question:** What logging/monitoring applies to the public website vs the operational system?
- **Why it matters:** Public traffic is anonymous and spam-prone (R3); operational audit needs are different. Spatie Activity Log is already present for admin actions.
- **Available Options:**
  - **A. Admin actions → Spatie Activity Log (existing); public page errors → structured app logs; inquiry submissions → activity log entry; external uptime monitoring.**
  - **B. Everything to one log.**
  - **C. No monitoring for public.**
- **Pros / Cons:** A — clean separation, reuses existing infra. B — noisy. C — blind to outages.
- **Recommendation:** **A.**
- **Reasoning:** Reuses the present Activity Log for admin (PRD §13 recommends audit log) and keeps public telemetry lightweight.
- **Risks:** Log volume from public errors. Mitigation: log levels + retention policy in ARD-001.

### Decision AR-22 — Configuration & environment strategy
- **Question:** Where do website-specific values (broker WhatsApp, broker email, GA4 ID, social links, brand assets) live?
- **Why it matters:** PRD Open Question #3 (broker contact) and #9 (domain) are config-driven; hardcoding forces deploys.
- **Available Options:**
  - **A. `config/` + `.env` for secrets (GA4 ID, broker email) and a CMS-stored "site settings" record for marketing-editable values (social links, contact display).**
  - **B. Everything in `.env`.**
  - **C. Everything in the DB.**
- **Pros / Cons:** A — secrets safe, marketing values editable without deploy. B — non-technical users can't change. C — secrets in DB (bad practice).
- **Recommendation:** **A.**
- **Reasoning:** Separates "secret/environmental" from "marketing-editable." Matches PRD's "no code deploy for content" (AC-10).
- **Risks:** Mixing secret and marketing config. Mitigation: ARD-001 lists which keys are env vs CMS.

### Decision AR-23 — Testing boundary
- **Question:** What is the minimum test strategy for the website module before launch?
- **Why it matters:** PRD §20.4 hard gates include a sensitive-data leakage test and security checklist. The repo already has PHPUnit.
- **Available Options:**
  - **A. Feature tests for public pages + a dedicated sensitive-data leakage test (asserts sensitive fields absent from public responses) + form validation tests; reuse existing PHPUnit setup.**
  - **B. Manual QA only.**
  - **C. Full E2E suite.**
- **Pros / Cons:** A — automates AC-4, fits existing tooling. B — leak risk (R1). C — over-built for MVP.
- **Recommendation:** **A.**
- **Reasoning:** AC-4 is too risky to leave manual. One automated leak test prevents the highest-impact risk.
- **Risks:** Leak test misses a new endpoint. Mitigation: test is parameterized over all public routes.

### Decision AR-24 — Localization architecture
- **Question:** If the ID/EN toggle (PRD FR-01 [RECOMMENDATION]) is approved, how is it architected?
- **Why it matters:** PRD Open Question #2 is unresolved. Building i18n later is cheap **if** the architecture doesn't hardcode Indonesian strings.
- **Available Options:**
  - **A. Use Laravel's built-in localization from day one for UI strings; store CMS content with optional translation fields (nullable EN columns) so EN can be filled later without schema churn.**
  - **B. Hardcode Indonesian everywhere; retrofit later.**
  - **C. Build full multi-locale now.**
- **Pros / Cons:** A — future-safe, near-zero MVP cost if EN is deferred. B — painful retrofit. C — speculative.
- **Recommendation:** **A**, applied **only** to UI strings and CMS content shape (not to building EN content). If stakeholders confirm ID-only, A still costs almost nothing.
- **Reasoning:** Keeps the EN door open without doing the EN work — consistent with AR-20.
- **Risks:** Slight schema overhead for nullable EN fields. Mitigation: acceptable; avoids a Phase-2 migration.

### Decision AR-25 — Backup & data retention
- **Question:** How are media and inquiry data backed up / retained (PRD §16.4: inquiries ≥ 12 months)?
- **Why it matters:** Public inquiry records contain personal data (privacy NFR); media is irreplaceable without re-upload.
- **Available Options:**
  - **A. Weekly DB + media backup (PRD NFR); inquiry soft-delete with 12-month minimum retention; no third-party sharing of personal data.**
  - **B. Backups only.**
  - **C. No backups.**
- **Pros / Cons:** A — meets PRD. B — misses retention. C — unacceptable.
- **Recommendation:** **A.**
- **Reasoning:** Direct PRD mapping.
- **Risks:** Backup not tested. Mitigation: ARD-001 includes a restore verification step.

---

## Architecture Decisions Summary

### Must Decide Before Development (block ARD-001 / Sprint 0)
These set the structural foundation; reversing them later is expensive.

| ID | Topic | Recommendation (short) |
|----|-------|------------------------|
| AR-01 | Deployment topology | Monolith inside existing app |
| AR-02 | App boundary | Module/namespace partition, no operational imports |
| AR-03 | Module architecture | Business-unit-scoped module (Line) now |
| AR-05 | CMS architecture | Dedicated new Filament panel |
| AR-06 | Domain: vessel entity | Distinct marketing entity, not operational `Vessel` |
| AR-10 | Auth boundary | Reuse `web` guard + users, dedicated CMS role, no branch scoping |
| AR-12 | Sensitive data isolation | Defense in depth + public projection + EXIF strip |
| AR-16 | Integration strategy | Zero operational integration in MVP; explicit integration port |
| AR-19 | Multi-business foundation | Structural readiness only, no speculative multi-tenant |
| AR-22 | Configuration strategy | Env for secrets + CMS-stored site settings |

### Can Decide During Development (within ARD-001 / Sprints)
Important but reversible or low-cost to settle while building.

| ID | Topic | Recommendation (short) |
|----|-------|------------------------|
| AR-04 | Public rendering | Blade default, Livewire only for interactive bits |
| AR-07 | Routing | Dedicated website route file, clean SEO URLs |
| AR-08 | Navigation | Template-driven for MVP |
| AR-09 | Shared components | Website-owned; share only generic primitives |
| AR-11 | Authorization | One CMS role via Spatie + resource policies |
| AR-13 | Media | Local disk + responsive variants + EXIF strip; S3-ready |
| AR-14 | Rich content | Sanitized WYSIWYG, allow-listed HTML |
| AR-15 | Inquiry flow | Persist form + queued email + GA4 events on all CTAs |
| AR-17 | Caching | Response caching with tag-based invalidation |
| AR-21 | Observability | Activity Log for admin; app logs for public; uptime monitor |
| AR-23 | Testing | Feature tests + automated sensitive-data leak test |
| AR-24 | Localization | Laravel i18n for UI; nullable EN content fields (defer EN work) |
| AR-25 | Backup/retention | Weekly DB+media backup; 12-month inquiry retention |

### Future Decision (explicitly deferred — do NOT build in MVP)
| ID | Topic | When |
|----|-------|------|
| AR-18 | Deploy pipeline detail | Stable once env/feature-flag approach (AR-18 part A) is confirmed in Sprint 0 |
| AR-20 | Extension points | Documented in ARD-001 as seams; implemented only when each PRD Phase 2–6 item is approved via CR |
| (derived from PRD §19) | CRM, owner portal, trust, multi-business, operational integration | Each requires a Change Request against frozen PRD-001 |

---

## Open Architecture Questions for the ARB

These are architecture-level questions that still need a business/stakeholder input (they echo PRD §21 where relevant but are framed here as architecture decisions):

1. **AR-06/AR-19:** Confirm the marketing vessel listing will have **no link** to the operational `Vessel` in MVP. (Affects data model and boundary.)
2. **AR-10:** Confirm a single CMS admin may exist alongside operational admins in the same users table without branch scoping. (Affects auth design.)
3. **AR-12:** Confirm the "public projection" approach is acceptable — i.e., sensitive fields are stored but structurally unreachable from public code paths. (Affects AC-4 assurance.)
4. **AR-13:** Confirm local-disk media is acceptable for MVP (vs. S3/CDN from day one). (Affects cost and ops.)
5. **AR-17:** Confirm response caching with invalidation is acceptable (vs. a CDN in front of the app). (Affects perf and ops.)
6. **AR-24:** Confirm whether the ID/EN toggle is in MVP. If undecided, confirm the "i18n-ready, EN-deferred" structural choice is acceptable.
7. **AR-05:** Confirm a fourth Filament panel is acceptable operationally (alongside admin, field-coordinator, customer).

---

**End of Architecture Review.**
This document is a review of decisions, not the design itself. Upon ARB approval, the chosen options become inputs to **ARD-001 — Architecture Design Document**, which will formalize the selected architecture.
