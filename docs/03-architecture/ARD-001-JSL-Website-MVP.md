# Architecture Design Document (ARD)
## ARD-001 — Jaya Sakti Line Website MVP
### Official Implementation Blueprint

**Project:** Jaya Sakti App
**Document:** ARD-001 — Architecture Design Document
**Version:** 1.0.0
**Status:** ✅ APPROVED — Implementation Blueprint
**Document Owner:** Architecture Team
**Last Updated:** 2026-06-30
**Next Phase:** DBD-001 — Database Design Document
**Sources of Truth (in precedence order):**
1. `docs/03-architecture/ADR-001.md` (v1.0.0, Accepted) — **Architectural Constitution; highest authority**
2. `docs/03-architecture/ARCHITECTURE_REVIEW.md` (v0.1.0, Accepted by ARB)
3. `docs/02-product/PRD-001-JSL-Website-MVP.md` (v1.0.0, Frozen)

> **Governance Rule**
> If any implementation detail in this document conflicts with an Accepted ADR, the **ADR always wins**. This document introduces **no new architecture decisions**. It only transforms accepted ADRs into an implementation blueprint.

---

## Change History

| Version | Date | Author | Description |
|---------|------|--------|-------------|
| 1.0.0 | 2026-06-30 | Architecture Team | Initial ARD — implementation blueprint derived from ADR-001 through ADR-010. |

---

## Table of Contents

1. Document Information
2. Architecture Vision
3. Architecture Principles
4. System Context Diagram (C4 Level 1)
5. Container Diagram (C4 Level 2)
6. Component Diagram (C4 Level 3)
7. Module Architecture
8. Domain Architecture
9. Request Flow
10. Security Architecture
11. Media Architecture
12. Routing Architecture
13. Application Layer Design
14. Infrastructure Layer
15. Deployment Architecture
16. Scalability Strategy
17. Quality Attributes
18. Architecture Constraints
19. Implementation Guidelines
20. Architecture Traceability Matrix
21. Glossary

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
| Predecessor Documents | PRD-001 (Frozen), Architecture Review (Accepted), ADR-001 (Accepted) |
| Successor Document | DBD-001 — Database Design Document |
| Authority | ADR-001 is the architectural constitution. This document must not contradict any Accepted ADR. |

---

## 2. Architecture Vision

**References: ADR-001 (Single Laravel Application), ADR-002 (Modular Monolith Architecture), ADR-007 (Public Website and CMS in Same Application)**

The Jaya Sakti Line Website MVP is implemented as a **single Laravel 11 application** that lives inside the existing `jss_dashboard` repository — the same runtime that already serves the JayaSaktiApp operational logistics system (ADR-001). The website is **not** a standalone application; it is a new module within an existing ecosystem.

Within that single application, the architecture is a **Modular Monolith** (ADR-002). The Jaya Sakti Line website is a **business-unit-scoped module** with its own namespace, its own domain models, its own Filament panel, and its own route group. A strict dependency rule — *the website module must not import operational namespaces, services, models, or Filament resources* — keeps the marketing domain isolated from the operational logistics domain.

The application exposes **two surfaces** (ADR-007): a **public website** (anonymous, server-rendered, SEO-friendly) and a **CMS** (authenticated, Filament-powered, internal). Both surfaces share the same codebase, the same MariaDB database (ADR-006), and the same platform kernel, but they are separated by route groups, middleware, views, and authorization.

The architecture is **structurally ready** for future Jaya Sakti business units (ADR-010) without building any multi-tenant feature in the MVP. A second unit would be added as a sibling module, not a restructure.

> **Architectural north star:** One app, one database, one deploy — with hard internal boundaries that keep the marketing domain, the operational domain, and future business units isolated from each other.

---

## 3. Architecture Principles

Every principle below is derived directly from an Accepted ADR. These principles govern all implementation decisions.

| # | Principle | Description | Governing ADR |
|---|-----------|-------------|---------------|
| P-1 | **Separation of Concerns** | Public website, CMS, and operational system are distinct concerns within one application, separated by modules, route groups, and middleware. | ADR-001, ADR-002, ADR-007 |
| P-2 | **Domain Isolation** | The marketing domain (vessel listings, inquiries, content) is a bounded context independent of the operational logistics domain. No cross-domain imports. | ADR-002, ADR-004 |
| P-3 | **Public Projection Pattern** | Public-facing code paths consume a read shape that structurally excludes sensitive fields. Sensitive data is never loaded into public contexts. | ADR-005 |
| P-4 | **CMS-Driven Content** | All public content (company profile, services, vessel listings, gallery) is managed via the CMS without code deploys. | ADR-003, ADR-008 |
| P-5 | **Modular Monolith** | The application is organized as modules with a shared platform kernel and per-unit feature modules. No distributed-system complexity. | ADR-002, ADR-010 |
| P-6 | **Single Deployment Unit** | One codebase, one database, one deploy artifact. Resource isolation is an infrastructure concern, not a codebase concern. | ADR-001, ADR-006, ADR-007 |
| P-7 | **Filament as Internal UI** | The CMS is built on Filament v3. No custom admin framework. Authorization is policy-based via Spatie Permission. | ADR-003, ADR-008 |
| P-8 | **Media Safety** | All uploaded media is EXIF-stripped, resized to responsive variants, and stored behind a media abstraction. Sensitive media never reaches the public disk. | ADR-005, ADR-009 |
| P-9 | **Structural Future-Readiness** | The architecture is structured so future business units and roadmap phases are additive, not rewrites. No speculative features are built. | ADR-010 |
| P-10 | **ADR Governance** | ADRs are the architectural constitution. Any conflict between implementation and ADR is resolved in favor of the ADR. | ADR-001 (Supersession Policy) |

---

## 4. System Context Diagram (C4 Level 1)

**References: ADR-001 (Single Laravel Application), ADR-007 (Public Website and CMS in Same Application), ADR-016/AR-16 (Integration Strategy — zero operational integration in MVP)**

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           EXTERNAL ACTORS                               │
│                                                                         │
│   ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌───────────┐  │
│   │  Public       │  │  Content      │  │  Broker /    │  │  Search   │  │
│   │  Visitor      │  │  Admin        │  │  Sales       │  │  Engine   │  │
│   │  (anonymous)  │  │  (CMS user)   │  │  (inquiry    │  │  Crawler  │  │
│   │               │  │               │  │   recipient) │  │           │  │
│   └──────┬───────┘  └──────┬───────┘  └──────┬───────┘  └─────┬─────┘  │
└──────────┼─────────────────┼─────────────────┼────────────────┼────────┘
           │                 │                 │                │
           │ Browse,         │ Manage          │ Receive        │ Crawl &
           │ inquire         │ content &       │ email          │ index
           │                 │ listings        │ notifications  │
           ▼                 ▼                 ▼                ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                        JayaSaktiApp (System)                            │
│                                                                         │
│   ┌───────────────────────────────────────────────────────────────────┐ │
│   │  Jaya Sakti Line Website Module                                    │ │
│   │                                                                    │ │
│   │   ┌─────────────────┐          ┌─────────────────┐                │ │
│   │   │  Public Website │          │  CMS (Filament) │                │ │
│   │   │  (anonymous)    │          │  (authenticated)│                │ │
│   │   └─────────────────┘          └─────────────────┘                │ │
│   └───────────────────────────────────────────────────────────────────┘ │
│                                                                         │
│   ┌───────────────────────────────────────────────────────────────────┐ │
│   │  Operational Logistics System (existing, out of scope)             │ │
│   │  No integration with website in MVP (ADR-002, AR-16)               │ │
│   └───────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────┘
           │                 │                 │
           ▼                 ▼                 ▼
┌─────────────────────┐ ┌──────────────┐ ┌──────────────────────────────┐
│  MariaDB            │ │  Local File  │ │  External Services           │
│  (Single DB)        │ │  Storage     │ │  - SMTP (email)              │
│  (ADR-006)          │ │  (ADR-009)   │ │  - WhatsApp (wa.me links)    │
│                     │ │              │ │  - Google Analytics (GA4)    │
│  Website tables +   │ │  Media +     │ │    (client-side tag)         │
│  Operational tables │ │  uploads     │ │                              │
│  (logically         │ │              │ │  No operational data flows   │
│   separated)        │ │              │ │  to/from the website in MVP  │
└─────────────────────┘ └──────────────┘ └──────────────────────────────┘
```

**Actors:**
- **Public Visitor** — anonymous internet user browsing the website and submitting inquiries (PRD Personas A, B).
- **Content Admin** — authenticated CMS user managing all website content (PRD Persona C).
- **Broker / Sales** — receives inquiry email notifications; not a CMS user in MVP (PRD Persona D).
- **Search Engine Crawler** — indexes public pages (SEO, PRD FR-10).

**External Systems:**
- **SMTP server** — sends broker notification and submitter auto-reply emails.
- **WhatsApp** — `wa.me` click-to-chat links (client-side only, no server integration).
- **Google Analytics (GA4)** — client-side analytics tag for conversion tracking.

**Key boundary (ADR-002 / AR-16):** The operational logistics system coexists in the same application but has **zero integration** with the website module in the MVP.

---

## 5. Container Diagram (C4 Level 2)

**References: ADR-001 (Single Laravel Application), ADR-006 (Single Database), ADR-007 (Public Website and CMS in Same Application), ADR-009 (Media Storage)**

```
┌─────────────────────────────────────────────────────────────────────────┐
│                              USER DEVICES                                │
│                                                                         │
│   ┌──────────────┐  ┌──────────────┐                                    │
│   │  Mobile       │  │  Desktop      │                                    │
│   │  Browser      │  │  Browser      │                                    │
│   │  (public +    │  │  (public +    │                                    │
│   │   CMS)        │  │   CMS)        │                                    │
│   └──────┬───────┘  └──────┬───────┘                                    │
└──────────┼─────────────────┼────────────────────────────────────────────┘
           │ HTTPS            │ HTTPS
           │                 │
           ▼                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                    JayaSaktiApp (Single Laravel App)                    │
│                    [ADR-001, ADR-007]                                    │
│                                                                         │
│   ┌───────────────────────────────────────────────────────────────────┐ │
│   │  Laravel 11 / PHP 8.3  (single runtime process)                  │ │
│   │                                                                   │ │
│   │  ┌─────────────────┐    ┌─────────────────┐                      │ │
│   │  │  Public Website  │    │  CMS (Filament)  │                      │ │
│   │  │  Route Group     │    │  Panel           │                      │ │
│   │  │  (anonymous)     │    │  (authenticated) │                      │ │
│   │  │  [ADR-007]       │    │  [ADR-003,ADR-008]│                      │ │
│   │  └────────┬────────┘    └────────┬────────┘                      │ │
│   │           │                       │                               │ │
│   │           ▼                       ▼                               │ │
│   │  ┌─────────────────────────────────────────────────────────────┐ │ │
│   │  │  Website Module (ADR-002)                                    │ │ │
│   │  │  - Controllers  - Services  - Actions  - Domain Models       │ │ │
│   │  │  - Policies     - Projections - Repositories - Resources     │ │ │
│   │  └─────────────────────────────────────────────────────────────┘ │ │
│   │           │                       │                               │ │
│   │           ▼                       ▼                               │ │
│   │  ┌─────────────────────────────────────────────────────────────┐ │ │
│   │  │  Platform Kernel (shared infra)                              │ │ │
│   │  │  - Auth (web guard) - Media abstraction - Cache - Queue      │ │ │
│   │  │  - Config - Logging - Mail                                   │ │ │
│   │  └─────────────────────────────────────────────────────────────┘ │ │
│   └───────────────────────────────────────────────────────────────────┘ │
└──────────┬──────────────────────┬───────────────────────┬───────────────┘
           │                      │                       │
           ▼                      ▼                       ▼
┌─────────────────────┐ ┌──────────────┐ ┌──────────────────────────────┐
│  MariaDB            │ │  Local File  │ │  Cache Store                 │
│  [ADR-006]          │ │  Storage     │ │  (file/Redis)                │
│                     │ │  [ADR-009]   │ │  [AR-17]                     │
│  Single database.   │ │              │ │                              │
│  Website tables +   │ │  storage/app │ │  Public page response cache  │
│  operational tables │ │  /public     │ │  with tag-based invalidation │
│  logically          │ │  (storage:   │ │                              │
│  separated by       │ │   link)      │ │                              │
│  naming/prefix      │ │              │ │                              │
└─────────────────────┘ └──────────────┘ └──────────────────────────────┘
```

**Containers:**

| Container | Technology | Role | ADR |
|-----------|-----------|------|-----|
| Browser | Any modern browser | Renders public pages + CMS UI | — |
| Laravel App | Laravel 11 / PHP 8.3 | Single runtime serving both public and CMS surfaces | ADR-001, ADR-007 |
| Filament CMS Panel | Filament v3 (inside Laravel) | Authenticated admin UI for content management | ADR-003, ADR-008 |
| MariaDB | MariaDB | Single database; website and operational tables logically separated | ADR-006 |
| Local File Storage | `storage/app/public` via `storage:link` | Media storage with responsive variants, EXIF-stripped | ADR-009 |
| Cache Store | File or Redis | Response caching for public pages with tag-based invalidation | AR-17 |

---

## 6. Component Diagram (C4 Level 3)

**References: ADR-002 (Modular Monolith), ADR-004 (Separate Marketing Domain Model), ADR-005 (Public Projection Pattern), ADR-008 (Filament as CMS)**

This diagram zooms into the **Website Module** (the Jaya Sakti Line unit) and its internal components.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  JayaSaktiApp — Laravel Application                                         │
│                                                                             │
│  ┌───────────────────────────────────────────────────────────────────────┐  │
│  │  WEBSITE MODULE (Jaya Sakti Line unit)  [ADR-002, ADR-010]             │  │
│  │                                                                        │  │
│  │  ┌─────────────── PUBLIC WEBSITE COMPONENTS ──────────────────┐       │  │
│  │  │                                                              │       │  │
│  │  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │       │  │
│  │  │  │ Company  │  │ Services │  │ Trading  │  │ Gallery  │   │       │  │
│  │  │  │ Component│  │ Component│  │ Component│  │ Component│   │       │  │
│  │  │  │          │  │          │  │          │  │          │   │       │  │
│  │  │  │ About,   │  │ Services │  │ Vessel   │  │ Gallery  │   │       │  │
│  │  │  │ Overview,│  │ listing  │  │ listing  │  │ display  │   │       │  │
│  │  │  │ Vision,  │  │ & detail │  │ index +  │  │          │   │       │  │
│  │  │  │ Mission  │  │          │  │ detail   │  │          │   │       │  │
│  │  │  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬─────┘   │       │  │
│  │  │       │             │             │             │          │       │  │
│  │  │       └─────────┬───┴─────────────┴─────────────┘          │       │  │
│  │  │                 │                                           │       │  │
│  │  │                 ▼                                           │       │  │
│  │  │  ┌──────────────────────────────────────────────────────┐ │       │  │
│  │  │  │  Inquiry Component                                    │ │       │  │
│  │  │  │  - WhatsApp link generation                           │ │       │  │
│  │  │  │  - Email (mailto) link generation                     │ │       │  │
│  │  │  │  - Inquiry form handling → Inquiry domain             │ │       │  │
│  │  │  └──────────────────────────────────────────────────────┘ │       │  │
│  │  └────────────────────────────────────────────────────────────┘       │  │
│  │                                                                        │  │
│  │  ┌─────────────── PROJECTION LAYER ──────────────────────────┐       │  │
│  │  │                                                              │       │  │
│  │  │  ┌──────────────────────────────────────────────────────┐ │       │  │
│  │  │  │  Projection Component  [ADR-005]                      │ │       │  │
│  │  │  │                                                        │ │       │  │
│  │  │  │  - VesselListingPublicProjection                      │ │       │  │
│  │  │  │    (general info only; sensitive fields excluded)     │ │       │  │
│  │  │  │  - All public components read via Projection only     │ │       │  │
│  │  │  │  - Direct model-to-view passing FORBIDDEN             │ │       │  │
│  │  │  └──────────────────────────────────────────────────────┘ │       │  │
│  │  └────────────────────────────────────────────────────────────┘       │  │
│  │                                                                        │  │
│  │  ┌─────────────── CMS COMPONENTS (Filament) ─────────────────┐       │  │
│  │  │                                                              │       │  │
│  │  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │       │  │
│  │  │  │ Company  │  │ Services │  │ Vessel   │  │ Gallery  │   │       │  │
│  │  │  │ Profile  │  │ Manager  │  │ Listings │  │ Manager  │   │       │  │
│  │  │  │ Editor   │  │ (CRUD)   │  │ Manager  │  │ (CRUD)   │   │       │  │
│  │  │  │ (CRUD)   │  │          │  │ (CRUD +  │  │          │   │       │  │
│  │  │  │          │  │          │  │  images) │  │          │   │       │  │
│  │  │  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │       │  │
│  │  │                                                              │       │  │
│  │  │  ┌──────────┐  ┌──────────────────────────────────────┐   │       │  │
│  │  │  │ Inquiry  │  │  Media Component (CMS-side)           │   │       │  │
│  │  │  │ Inbox    │  │  - Upload handler                      │   │       │  │
│  │  │  │ (View,   │  │  - EXIF stripping                      │   │       │  │
│  │  │  │  status) │  │  - Responsive variant generation       │   │       │  │
│  │  │  │          │  │  - Obfuscated filename assignment      │   │       │  │
│  │  │  └──────────┘  └──────────────────────────────────────┘   │       │  │
│  │  │                                                              │       │  │
│  │  │  [ADR-003: Dedicated CMS Panel]                             │       │  │
│  │  │  [ADR-008: Filament v3 + Spatie Permission + Policies]     │       │  │
│  │  └────────────────────────────────────────────────────────────┘       │  │
│  │                                                                        │  │
│  │  ┌─────────────── DOMAIN LAYER ──────────────────────────────┐       │  │
│  │  │                                                              │       │  │
│  │  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │       │  │
│  │  │  │ Marketing│  │ Inquiry  │  │ Content  │  │ Media    │   │       │  │
│  │  │  │ Domain   │  │ Domain   │  │ Domain   │  │ Domain   │   │       │  │
│  │  │  │          │  │          │  │          │  │          │   │       │  │
│  │  │  │ Vessel   │  │ Inquiry  │  │ Company  │  │ Media    │   │       │  │
│  │  │  │ Listing  │  │ records  │  │ Profile  │  │ assets   │   │       │  │
│  │  │  │ (ADR-004)│  │          │  │ Services │  │          │   │       │  │
│  │  │  │          │  │          │  │ Gallery  │  │          │   │       │  │
│  │  │  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │       │  │
│  │  └────────────────────────────────────────────────────────────┘       │  │
│  └───────────────────────────────────────────────────────────────────────┘  │
│                                                                             │
│  ┌───────────────────────────────────────────────────────────────────────┐  │
│  │  PLATFORM KERNEL (shared infrastructure)  [ADR-002, ADR-010]           │  │
│  │                                                                        │  │
│  │  Auth (web guard) │ Media Abstraction │ Cache │ Queue │ Config │       │  │
│  │  Logging │ Mail │ Spatie Permission │ Spatie Activity Log             │  │
│  └───────────────────────────────────────────────────────────────────────┘  │
│                                                                             │
│  ┌───────────────────────────────────────────────────────────────────────┐  │
│  │  OPERATIONAL LOGISTICS MODULE (existing — OUT OF SCOPE)                │  │
│  │  No imports from Website Module. No imports to Website Module.        │  │
│  │  [ADR-002: dependency rule]                                            │  │
│  └───────────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────┘
```

**Component responsibilities:**

| Component | Responsibility | Governing ADR |
|-----------|---------------|---------------|
| Company (Public) | Renders About, Overview, Vision & Mission pages from CMS-managed content | ADR-007 |
| Services (Public) | Renders services listing from CMS-managed data | ADR-007 |
| Trading (Public) | Renders vessel listing index + detail pages; consumes public projection only | ADR-004, ADR-005 |
| Gallery (Public) | Renders gallery from CMS-managed images | ADR-007 |
| Inquiry (Public) | Generates WhatsApp/Email links; handles inquiry form submission | ADR-007 |
| Projection | Provides sensitive-field-free read shapes for all public contexts | ADR-005 |
| Company Profile Editor (CMS) | Filament CRUD for company profile content | ADR-003, ADR-008 |
| Services Manager (CMS) | Filament CRUD for services | ADR-003, ADR-008 |
| Vessel Listings Manager (CMS) | Filament CRUD for vessel listings + image upload + status toggle | ADR-003, ADR-008 |
| Gallery Manager (CMS) | Filament CRUD for gallery | ADR-003, ADR-008 |
| Inquiry Inbox (CMS) | Filament resource for viewing/managing inquiry submissions | ADR-003, ADR-008 |
| Media (CMS) | Upload handling, EXIF stripping, variant generation, obfuscated paths | ADR-009 |
| Marketing Domain | VesselListing entity (general + sensitive fields), lifecycle, status | ADR-004 |
| Inquiry Domain | Inquiry records, channels, retention | — |
| Content Domain | Company profile, services, gallery content entities | — |
| Media Domain | Media asset entities, variant metadata | ADR-009 |
| Platform Kernel | Shared infrastructure (auth, media abstraction, cache, queue, config, logging, mail) | ADR-002, ADR-010 |

---

## 7. Module Architecture

**References: ADR-002 (Modular Monolith Architecture), ADR-010 (Future Multi-Business Ready Architecture)**

### 7.1 Module Structure

The application consists of three top-level module groups:

```
app/
├── Modules/
│   └── Website/                          ← Website Module (Jaya Sakti Line unit)
│       └── Jsl/                          ← Jaya Sakti Line business unit
│           ├── Http/Controllers/         ← Public controllers
│           ├── Filament/                 ← CMS panel + resources
│           ├── Models/                   ← Marketing domain models
│           ├── Services/                 ← Application services
│           ├── Actions/                  ← Single-purpose action classes
│           ├── Repositories/             ← Data access
│           ├── Projections/             ← Public read shapes (ADR-005)
│           ├── Policies/                ← Authorization policies
│           ├── Resources/               ← API/transformer resources
│           └── Routes/                  ← Website route file
│
├── (existing operational code)           ← Operational logistics module
│   ├── Filament/Resources/              ← Operational Filament resources
│   ├── Models/  (incl. Vessel.php)      ← Operational models (ADR-004: NOT reused)
│   └── ...
│
└── Platform/                             ← Platform Kernel (shared infra)
    ├── Media/                            ← Media abstraction (ADR-009)
    ├── Auth/                             ← Auth concerns
    └── ...
```

> **Note:** The exact folder structure is indicative. Final paths are confirmed in ARD-001 implementation and DBD-001. The **module boundary rules** below are authoritative regardless of exact path.

### 7.2 Module Responsibilities

| Module | Owns | Does NOT Own |
|--------|------|--------------|
| **Website Module (JSL)** | All public website pages, all CMS resources for website content, marketing domain models, inquiry handling, media upload for website, projections, policies, website routes | Operational models, operational services, operational Filament resources |
| **Operational Logistics Module** | Shipment, voyage, vessel plan, KPI, branch scoping, operational `Vessel` model | Any marketing/public website concern |
| **Platform Kernel** | Auth guard, media abstraction interface, cache config, queue config, mail config, logging, Spatie Permission/ActivityLog integration | Domain logic of any unit |

### 7.3 Dependency Rules

**Allowed communication:**

| From | To | Allowed? |
|------|----|---------|
| Website Module | Platform Kernel | ✅ Yes |
| Website Module (CMS) | Website Module (Domain) | ✅ Yes |
| Website Module (Public) | Website Module (Projection) | ✅ Yes |
| Website Module (Projection) | Website Module (Domain) | ✅ Yes (read-only, sensitive fields excluded) |
| Operational Module | Platform Kernel | ✅ Yes |
| Website Module | Operational Module | ❌ **FORBIDDEN** |
| Operational Module | Website Module | ❌ **FORBIDDEN** |
| Public Component | Domain Model (direct) | ❌ **FORBIDDEN** — must go via Projection |
| CMS Component | Domain Model (full) | ✅ Yes (CMS may access full entity incl. sensitive fields) |

**Forbidden communication (enforced by ADR-002):**

1. The Website Module **must not import** any operational namespace (`App\Models\Vessel`, `App\Filament\Resources\VesselResource`, operational services, etc.).
2. The Operational Module **must not import** any website module namespace.
3. Public components **must not receive** the full marketing domain entity — only its public projection (ADR-005).
4. No foreign keys or query joins cross the marketing/operational boundary (ADR-006).

> **Enforcement:** These rules are checkable via code review and CI static analysis. A CI rule should verify that files under the website module namespace do not reference operational namespaces.

---

## 8. Domain Architecture

**References: ADR-002 (Modular Monolith), ADR-004 (Separate Marketing Domain Model), ADR-005 (Public Projection Pattern), ADR-010 (Future Multi-Business Ready Architecture)**

### 8.1 Domain Map

```
┌─────────────────────────────────────────────────────────────┐
│  WEBSITE MODULE — Domains                                    │
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │  Content     │  │  Marketing   │  │  Inquiry     │       │
│  │  Domain      │  │  Domain      │  │  Domain      │       │
│  │              │  │              │  │              │       │
│  │  - Company   │  │  - Vessel    │  │  - Inquiry   │       │
│  │    Profile   │  │    Listing   │  │    Record    │       │
│  │  - Service   │  │    (ADR-004) │  │  - Channels  │       │
│  │  - Gallery   │  │  - Listing   │  │    (WA/Email │       │
│  │    Item      │  │    Lifecycle │  │    /Form)    │       │
│  │              │  │  - Status    │  │  - Retention │       │
│  └──────────────┘  └──────────────┘  └──────────────┘       │
│                                                              │
│  ┌──────────────┐                                            │
│  │  Media       │  ┌──────────────────────────────────────┐  │
│  │  Domain      │  │  Projection Layer (cross-cutting)     │  │
│  │              │  │  [ADR-005]                            │  │
│  │  - Media     │  │                                      │  │
│  │    Asset     │  │  - VesselListingPublicProjection      │  │
│  │  - Variants  │  │  - Applies to: Marketing Domain       │  │
│  │  - EXIF      │  │  - Consumed by: Public components     │  │
│  │    Strip     │  │  - NOT consumed by: CMS components    │  │
│  └──────────────┘  └──────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  FUTURE MODULES (NOT in MVP — ADR-010)                       │
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │  JSS Website │  │  Broker      │  │  Group       │       │
│  │  Module      │  │  Module      │  │  Website     │       │
│  │  (Phase 5)   │  │  (Phase 2-3) │  │  (Phase 5)   │       │
│  └──────────────┘  └──────────────┘  └──────────────┘       │
│  Each added as a sibling module. No kernel rewrite.          │
└─────────────────────────────────────────────────────────────┘
```

### 8.2 Domain Descriptions

#### Content Domain
- **Purpose:** Manages CMS-driven website content — company profile (about, overview, vision, mission), services, and gallery items.
- **Entities:** CompanyProfile (singleton-style), Service, GalleryItem.
- **Access:** CMS has full CRUD; public components read via services/repositories (content is not sensitive, so no projection needed — but the same repository pattern is used for consistency).
- **Governing ADR:** ADR-002 (module isolation), ADR-008 (CMS via Filament).

#### Marketing Domain
- **Purpose:** Manages vessel trading listings — the core marketing offering.
- **Entities:** VesselListing (with general-info fields + sensitive internal fields), VesselImage (≤6 per listing, ordered).
- **Critical rule:** The VesselListing is a **distinct entity** from the operational `Vessel` (ADR-004). No relationship, foreign key, or code dependency connects them.
- **Lifecycle:** Open (default) → Closed; soft-delete recommended.
- **Access:** CMS has full access to all fields including sensitive ones. Public components access **only** via the Projection Layer (ADR-005).
- **Governing ADR:** ADR-004, ADR-005.

#### Inquiry Domain
- **Purpose:** Captures and stores form-based inquiries; generates WhatsApp/Email links.
- **Entities:** Inquiry (name, company, email, phone, message, vessel reference nullable, status, timestamp).
- **Channels:** WhatsApp and Email are client-side links (no server record); Form submissions are persisted and notified via queued email.
- **Retention:** Inquiry records retained ≥ 12 months (PRD §16.4).
- **Governing ADR:** ADR-007 (public surface), AR-15 (inquiry flow).

#### Media Domain
- **Purpose:** Manages media assets for vessel listings and gallery.
- **Entities:** MediaAsset (path, variants metadata, associated entity).
- **Pipeline:** Upload → EXIF strip → Resize to responsive variants → Store with obfuscated filename.
- **Access:** CMS manages uploads; public components consume optimized variants.
- **Governing ADR:** ADR-009.

#### Projection Layer (Cross-Cutting)
- **Purpose:** Provides sensitive-field-free read shapes for public consumption (ADR-005).
- **Scope:** Applies to the Marketing Domain (VesselListing). Other domains do not have sensitive fields but use the same repository→resource pattern for consistency.
- **Rule:** Public controllers/views **never** receive the full VesselListing entity. They receive a `VesselListingPublicProjection` that structurally excludes: real vessel name, IMO number, owner, certificates, price/commercial terms.
- **Governing ADR:** ADR-005.

#### Future Modules (NOT in MVP)
- **JSS Website Module** (Phase 5) — a second business unit, added as a sibling to the JSL module.
- **Broker Module** (Phase 2-3) — inquiry pipeline, CRM sync, broker assignment.
- **Group Website** (Phase 5) — group-level branding site.
- All future modules are additive siblings under `Modules/`. They share the Platform Kernel but do not import each other's domain code (ADR-010).

---

## 9. Request Flow

**References: ADR-005 (Public Projection Pattern), ADR-007 (Public Website and CMS in Same Application), ADR-008 (Filament as CMS)**

### 9.1 Public Request Flow

```
Public Visitor (Browser)
    │
    │  HTTPS request (e.g., GET /vessels/JSL-001)
    │
    ▼
┌──────────────────┐
│  Web Middleware   │  HTTPS, CSRF, share errors, substitute bindings
│  (Laravel stack)  │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  Route Matching   │  Website route file → public route group
│  (anonymous)      │  No auth middleware
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  Controller       │  e.g., VesselDetailController
│  (Public)         │  Receives route params, validates input
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  Application      │  Service or Action class
│  Layer            │  Orchestrates domain access
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  Repository       │  Fetches VesselListing from database
│  (read-only)      │  Returns full entity to the application layer
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  Projection Layer │  Transforms full entity → PublicProjection
│  [ADR-005]        │  Sensitive fields STRUCTURALLY EXCLUDED
│                   │  (name, IMO, owner, certificates, price)
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  View (Blade)     │  Receives ONLY the projection
│                   │  Renders general info + images + CTAs
│                   │  CANNOT access sensitive fields (never loaded)
└────────┬─────────┘
         │
         ▼
    HTTP Response (HTML)
    (cached via response caching, AR-17)
```

**Key rule (ADR-005):** The View layer never receives the full entity. The Projection Layer is the mandatory bridge between the domain and the public view. Bypassing it is an architecture violation (see §18).

### 9.2 Admin (CMS) Request Flow

```
Content Admin (Browser)
    │
    │  HTTPS request (e.g., POST /cms/vessel-listings)
    │
    ▼
┌──────────────────┐
│  CMS Middleware   │  Auth (web guard), CMS role check (EnsurePanelRole),
│  Stack            │  CSRF, session — NO ScopeByBranch (ADR-003)
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  Filament Panel   │  Dedicated CMS panel (ADR-003)
│  Router           │  Routes to Filament Resource
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  Filament         │  e.g., VesselListingResource
│  Resource         │  Form/Table definitions, file upload fields
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  Policy           │  Authorization check (Spatie Permission + Eloquent Policy)
│  [ADR-008]        │  Verifies CMS role can perform action
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  Application      │  Service/Action for business logic
│  Layer            │  (e.g., MediaService for image upload + EXIF strip)
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  Domain Layer     │  VesselListing entity (FULL access)
│  (CMS context)    │  Including sensitive fields (name, IMO, owner)
│                   │  CMS IS allowed to see/edit all fields
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  Repository       │  Persists to database (single MariaDB, ADR-006)
│  (read/write)     │  Media saved to local disk via Media abstraction (ADR-009)
└────────┬─────────┘
         │
         ▼
    Filament UI Response
    (redirect / updated table — NOT cached)
```

**Key difference from public flow:** The CMS flow has **full domain access** including sensitive fields. The Projection Layer is **not involved** in the CMS flow — it exists solely to protect the public surface (ADR-005).

### 9.3 Inquiry Submission Flow

```
Public Visitor
    │
    │  POST /inquiries (form submission)
    │
    ▼
┌──────────────────┐
│  Web Middleware   │  CSRF, spam protection (honeypot/rate-limit) [AR-12]
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  Controller       │  InquiryController@store
│  Validates input  │  Name (req), Email or Phone (≥1 req), Message (req)
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  Action           │  SubmitInquiryAction
│  (application)    │  Persists inquiry record
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  Domain           │  Inquiry entity saved to database
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  Queue            │  Dispatches queued jobs:
│                   │  1. Broker notification email
│                   │  2. Submitter auto-reply email (if enabled)
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  GA4 Event        │  Client-side dataLayer push (conversion tracking)
│  (client-side)    │
└────────┬─────────┘
         │
         ▼
    Redirect with success confirmation
```

**WhatsApp / Email clicks:** These are client-side `wa.me` / `mailto:` links with GA4 event tracking. No server record is created (PRD §16.3, AR-15).

---

## 10. Security Architecture

**References: ADR-005 (Public Projection Pattern), ADR-003 (Dedicated CMS Panel), ADR-008 (Filament as CMS), AR-12 (Sensitive data boundary), AR-10 (Auth boundary)**

### 10.1 Authentication

| Surface | Guard | Auth Method | Scoping | ADR |
|---------|-------|------------|---------|-----|
| Public Website | None (anonymous) | N/A | N/A | ADR-007 |
| CMS Panel | `web` guard (existing) | Email/username + password, hashed, rate-limited login | **No branch scoping** (ADR-003) | ADR-003, ADR-008, AR-10 |
| Operational panels | `web` guard (existing) | Existing operational auth | `ScopeByBranch` + `EnsurePanelRole` | (out of scope) |

**Key decision (ADR-003 / AR-10):** The CMS reuses the existing `web` guard and users table but **excludes `ScopeByBranch`** from its middleware stack. A dedicated CMS role gates access. CMS admins are marketing users, not operational branch users.

### 10.2 Authorization

| Surface | Mechanism | Roles (MVP) | Future | ADR |
|---------|-----------|-------------|--------|-----|
| Public Website | None | N/A | N/A | ADR-007 |
| CMS Panel | Spatie Permission + Eloquent Policies | Single CMS role | Editor, Broker (Phase 2/3) — additive | ADR-008, AR-11 |

**Rules (ADR-008 / AR-11):**
- All CMS authorization goes through **resource-level Eloquent Policies**.
- Inline `if (admin)` checks are **prohibited**.
- Adding future roles is a permission/role change, not an architecture change.

### 10.3 Sensitive Data Boundary

```
┌─────────────────────────────────────────────────────────┐
│                    SENSITIVE DATA BOUNDARY               │
│                    [ADR-005]                             │
│                                                         │
│  Sensitive fields (NEVER public):                       │
│  - Vessel real name                                     │
│  - IMO number                                           │
│  - Owner / ownership details                            │
│  - Full certificates                                    │
│  - Price / commercial terms (recommended)               │
│                                                         │
│  ┌─────────────────┐        ┌─────────────────┐         │
│  │  CMS Context    │        │  Public Context  │         │
│  │  (authenticated)│        │  (anonymous)     │         │
│  │                 │        │                  │         │
│  │  CAN access     │        │  CANNOT access   │         │
│  │  all fields     │        │  sensitive fields│         │
│  │  including      │        │  at all.         │         │
│  │  sensitive ones │        │                  │         │
│  └─────────────────┘        └─────────────────┘         │
│         │                           │                   │
│         │ Full entity               │ Projection only   │
│         ▼                           ▼                   │
│  ┌─────────────────┐        ┌─────────────────┐         │
│  │  Database       │        │  Projection     │         │
│  │  (full row)     │        │  Layer          │         │
│  │                 │        │  (stripped)     │         │
│  └─────────────────┘        └─────────────────┘         │
└─────────────────────────────────────────────────────────┘
```

**Defense-in-depth controls (ADR-005):**

| Layer | Control |
|-------|---------|
| Data access | Public repositories/projections select only non-sensitive columns |
| Application layer | Public controllers receive projection objects, not full entities |
| View layer | Blade templates receive projection; sensitive fields are never in variable scope |
| Media | EXIF/metadata stripped on upload (owner/identity leakage prevention) |
| Testing | Automated parameterized leak test across all public routes (AR-23) |
| Process | PRD §20.4 hard gate: sensitive data leakage test before launch |
| Rule | Allow-list for public fields; never block-list |

### 10.4 Media Protection

| Concern | Control | ADR |
|---------|---------|-----|
| EXIF/metadata leakage | Strip on upload before storage | ADR-005, ADR-009 |
| Predictable filenames | Obfuscated (non-guessable) filenames for public media | ADR-009 |
| Admin-only media | Stored on non-public disk; never placed on public disk | ADR-009 |
| Image alt text | Alt text must not contain sensitive data (vessel name, IMO, owner) | ADR-005 |

### 10.5 Form Security

| Concern | Control | ADR/AR |
|---------|---------|--------|
| CSRF | Laravel CSRF tokens on all form submissions | AR-12 |
| Spam | Honeypot field + rate limiting on inquiry form | AR-12 |
| Privacy | Consent checkbox for data use; no third-party data sharing | PRD NFR |
| HTTPS | Enforced site-wide; admin routes protected | PRD AC-14 |

---

## 11. Media Architecture

**References: ADR-009 (Media Storage Strategy), ADR-005 (Public Projection Pattern — EXIF stripping)**

### 11.1 Media Pipeline

```
┌──────────────────────────────────────────────────────────────────┐
│  MEDIA PIPELINE (CMS upload → public delivery)                   │
│  [ADR-009]                                                       │
│                                                                  │
│  Step 1: UPLOAD                                                   │
│  ┌──────────────┐                                                │
│  │  Admin        │  Selects image in Filament file upload field   │
│  │  (CMS)        │  Max size enforced (e.g., 5MB)                 │
│  └──────┬───────┘                                                │
│         │                                                        │
│         ▼                                                        │
│  Step 2: EXIF STRIP [ADR-005]                                    │
│  ┌──────────────┐                                                │
│  │  Media        │  Strip all EXIF/metadata from image            │
│  │  Service      │  Prevents owner/vessel identity leakage        │
│  └──────┬───────┘                                                │
│         │                                                        │
│         ▼                                                        │
│  Step 3: RESIZE — Responsive Variants                             │
│  ┌──────────────┐                                                │
│  │  Media        │  Generate variants:                           │
│  │  Service      │  - Thumbnail (listing index)                  │
│  │               │  - Medium (detail page)                       │
│  │               │  - Large (full view)                          │
│  │               │  Format: WebP (or JPEG fallback)              │
│  └──────┬───────┘                                                │
│         │                                                        │
│         ▼                                                        │
│  Step 4: STORE — Obfuscated Paths                                 │
│  ┌──────────────┐                                                │
│  │  Filesystem   │  Local `public` disk (storage:link)            │
│  │  (local)      │  Obfuscated filename (non-guessable)           │
│  │               │  Path: storage/app/public/jsl-media/...        │
│  │               │  Admin-only media: non-public disk             │
│  └──────┬───────┘                                                │
│         │                                                        │
│         ▼                                                        │
│  Step 5: DELIVERY — Public                                        │
│  ┌──────────────┐                                                │
│  │  Blade View   │  <img srcset="..." loading="lazy">             │
│  │  (public)     │  Responsive srcset with variants               │
│  │               │  Lazy loading for LCP < 3s (AC-8)              │
│  └──────────────┘                                                │
└──────────────────────────────────────────────────────────────────┘
```

### 11.2 Media Abstraction

A **Media abstraction** (service/interface within the Platform Kernel) encapsulates all filesystem and image-processing concerns. The Website Module interacts with this abstraction, not with raw filesystem calls.

**Why (ADR-009):** When the project moves to S3/CDN, only the abstraction's disk configuration changes. No website-module code is rewritten.

### 11.3 Media per Entity

| Entity | Max Images | Ordering | Thumbnail | ADR |
|--------|-----------|----------|-----------|-----|
| Vessel Listing | 6 | Admin-controlled, persisted | First image | ADR-009, PRD §15.4 |
| Gallery Item | 1 per item | Admin-controlled order | N/A | ADR-009 |
| Service | 1 (icon/image, optional) | N/A | N/A | ADR-009 |
| Company Profile | Inline rich-text images | Via WYSIWYG | N/A | ADR-009 |

### 11.4 Variant Sizes (indicative — finalized in DBD-001/implementation)

| Variant | Use Case | Approximate Width |
|---------|----------|-------------------|
| Thumbnail | Listing index card | ~400px |
| Medium | Detail page main image | ~800px |
| Large | Full-size viewer | ~1200px |

> Exact dimensions, format (WebP/JPEG), and quality settings are confirmed during implementation. The architecture does not prescribe pixel values.

---

## 12. Routing Architecture

**References: ADR-007 (Public Website and CMS in Same Application), ADR-003 (Dedicated CMS Panel), AR-07 (Routing strategy)**

### 12.1 Route Organization

```
routes/
├── web.php                    ← Existing operational routes (landing, tracking, print)
└── (loaded by service provider):
    └── website.php            ← Website module route file (loaded into web.php or via provider)
```

The website module registers its routes via a dedicated route file loaded through the module's service provider. Route names use a prefix convention (e.g., `jsl.public.*`) to avoid collisions with existing operational route names (`landing`, `tracking`, etc.).

### 12.2 Public Routes

```
Route Group: public (anonymous, no auth middleware)
├── GET  /                     → HomeController@index        (jsl.public.home)
├── GET  /about                → AboutController@index       (jsl.public.about)
├── GET  /services             → ServiceController@index     (jsl.public.services)
├── GET  /vessels              → VesselListingController@index (jsl.public.vessels.index)
├── GET  /vessels/{ref}        → VesselListingController@show  (jsl.public.vessels.show)
├── GET  /gallery              → GalleryController@index     (jsl.public.gallery)
├── GET  /contact              → ContactController@index     (jsl.public.contact)
└── POST /inquiries            → InquiryController@store     (jsl.public.inquiries.store)
```

**Middleware (public group):**
- HTTPS enforcement
- CSRF (for POST)
- Spam protection (honeypot + rate limit on `/inquiries`)
- Response caching (tag-based invalidation, AR-17)
- No authentication

### 12.3 CMS Routes

```
Filament CMS Panel (ADR-003)
├── Path: /cms (or /admin/jsl — finalized in implementation)
├── Login: /cms/login
├── Dashboard: /cms
├── Company Profile: /cms/company-profile
├── Services: /cms/services
├── Vessel Listings: /cms/vessel-listings
├── Gallery: /cms/gallery
└── Inquiries: /cms/inquiries
```

**Middleware (CMS panel stack — ADR-003):**
- Auth (`web` guard)
- CMS role check (`EnsurePanelRole` extended for CMS role)
- CSRF, session, cookies
- **NO `ScopeByBranch`** (ADR-003 — critical distinction from operational admin)
- No response caching (admin content must be fresh)

### 12.4 Route Naming Convention

| Surface | Prefix | Example |
|---------|--------|---------|
| Public website | `jsl.public.*` | `jsl.public.vessels.show` |
| CMS (Filament) | Filament auto-generated | (managed by Filament) |
| Operational (existing) | existing names | `landing`, `tracking`, `shipments.print.waybill` |

The `jsl.` prefix prevents collisions and supports future business units (a future JSS module would use `jss.public.*`).

---

## 13. Application Layer Design

**References: ADR-002 (Modular Monolith), ADR-005 (Public Projection Pattern), ADR-008 (Filament as CMS)**

This section describes the **responsibilities** of each application-layer component type. No code is included.

### 13.1 Controllers (Public)

| Responsibility | Detail |
|----------------|--------|
| Receive HTTP requests | Parse route parameters, query parameters |
| Validate input | Form Requests for validation rules |
| Delegate to application layer | Call services/actions; never access domain models directly |
| Return responses | Return Blade views (with projection data) or redirects |
| No business logic | Controllers are thin; logic lives in services/actions |
| No direct model access | Controllers receive projections (ADR-005), never full entities |

### 13.2 Services

| Responsibility | Detail |
|----------------|--------|
| Orchestrate business operations | Coordinate between repositories, actions, media, mail |
| Use-case level logic | e.g., `VesselListingService` handles listing retrieval for public context (returns projection) |
| Transactional boundaries | Wrap multi-step operations in DB transactions where needed |
| CMS + Public variants | Same service class may serve both contexts, but public methods return projections only |

### 13.3 Actions

| Responsibility | Detail |
|----------------|--------|
| Single-purpose operations | One action = one use case (e.g., `SubmitInquiryAction`, `ToggleListingStatusAction`) |
| Invoked from controllers or Filament resources | |
| Side-effect isolation | Actions that trigger emails/notifications dispatch queue jobs |
| Testable in isolation | Each action is unit-testable without HTTP context |

### 13.4 Repositories

| Responsibility | Detail |
|----------------|--------|
| Data access abstraction | Encapsulate MariaDB queries for website module entities |
| Read methods (public) | Return entities to the application layer; the application layer then maps to projection |
| Read/Write methods (CMS) | Full CRUD access including sensitive fields |
| No cross-domain queries | Repositories query only website-module tables (ADR-002, ADR-006) |
| Cache interaction | Public read methods may interact with cache (AR-17); CMS methods do not cache |

### 13.5 Policies

| Responsibility | Detail |
|----------------|--------|
| Authorization per resource | Eloquent Policy per CMS Filament resource (ADR-008) |
| Role-based | Check CMS role via Spatie Permission |
| Method-level | `viewAny`, `view`, `create`, `update`, `delete`, `restore` |
| Future-ready | Granular methods allow per-role restrictions without restructure (AR-11) |
| No inline checks | All authorization goes through policies; inline `if` checks are prohibited |

### 13.6 Resources (Transformers)

| Responsibility | Detail |
|----------------|--------|
| Public projection | Transform domain entities into public-facing shapes (ADR-005) |
| Sensitive field exclusion | Projection resources structurally exclude sensitive fields |
| API responses (if any) | If public API endpoints are added, they use the same projection resources |
| CMS resources | CMS uses Filament's built-in table/form rendering; separate API resources not needed unless headless API is added (future) |

### 13.7 Projections

| Responsibility | Detail |
|----------------|--------|
| Public read shape | A dedicated object/class representing the public-safe subset of a domain entity |
| Sensitive field exclusion | Does not include: real vessel name, IMO, owner, certificates, price |
| Mandatory for public | All public controllers/views consume projections, never full entities (ADR-005) |
| Not used in CMS | CMS components access full entities directly (sensitive fields visible to admin) |

---

## 14. Infrastructure Layer

**References: ADR-001 (Single Laravel Application), ADR-006 (Single Database), ADR-009 (Media Storage), ADR-008 (Filament), AR-17 (Caching), AR-21 (Observability)**

### 14.1 Infrastructure Components

| Component | Technology | Role | Config Source | ADR/AR |
|-----------|-----------|------|---------------|--------|
| **Laravel 11** | PHP 8.3 framework | Application framework, HTTP kernel, routing, middleware, Eloquent ORM | `config/app.php`, `.env` | ADR-001 |
| **Filament v3** | Admin panel framework | CMS panel (dedicated 4th panel), CRUD resources, file uploads, tables | `config/filament.php`, panel provider | ADR-003, ADR-008 |
| **MariaDB** | Relational database | Single database; website + operational tables logically separated by naming/prefix | `config/database.php`, `.env` | ADR-006 |
| **Storage (Local)** | `storage/app/public` via `storage:link` | Media storage (responsive variants, EXIF-stripped, obfuscated paths) | `config/filesystems.php`, `.env` | ADR-009 |
| **Cache** | File or Redis | Response caching for public pages; tag-based invalidation on CMS update | `config/cache.php`, `.env` | AR-17 |
| **Queue** | Laravel Queue (sync or Redis/database driver) | Async email dispatch (broker notification, auto-reply) on inquiry submission | `config/queue.php`, `.env` | AR-15 |
| **Logging** | Laravel Logging (Monolog) | Public page errors → structured logs; admin actions → Spatie Activity Log | `config/logging.php` | AR-21 |
| **Mail** | Laravel Mail (SMTP) | Broker notification + submitter auto-reply emails | `config/mail.php`, `.env` | AR-15 |
| **Spatie Permission** | Package | Role-based authorization (CMS role; future roles additive) | `config/permission.php` | ADR-008 |
| **Spatie Activity Log** | Package | Audit log for CMS admin actions | (package config) | AR-21 |
| **Sanctum** | Package | Present for API auth (not used by website MVP; available for future API) | `config/sanctum.php` | (existing infra) |

### 14.2 Configuration Strategy

**References: AR-22 (Configuration & environment strategy)**

| Config Type | Storage | Example | Editable By |
|-------------|---------|---------|-------------|
| Secrets / environmental | `.env` | GA4 ID, SMTP credentials, broker email, broker WhatsApp number | Dev/Ops only |
| Marketing-editable site settings | CMS-stored settings record | Social links, displayed contact info, brand text | Content Admin (no deploy) |
| Module structure | `config/` files | Route prefixes, cache TTLs, media variant sizes | Dev only |

**Rule (AR-22):** Secrets never live in the CMS or database. Marketing-editable values never require a code deploy.

---

## 15. Deployment Architecture

**References: ADR-001 (Single Laravel Application), ADR-006 (Single Database), ADR-009 (Media Storage), AR-18 (Deployment strategy)**

### 15.1 Deployment Topology

```
┌─────────────────────────────────────────────────────────────────┐
│                      INTERNET                                   │
└─────────┬───────────────────────────────────────────────────────┘
          │
          ▼
┌─────────────────────────┐
│  Cloudflare (DNS +      │  - DNS resolution
│  CDN + WAF + SSL)       │  - CDN caching for static assets
│  [optional for MVP,     │  - WAF rules (basic protection)
│   recommended]          │  - SSL termination
└─────────┬───────────────┘
          │
          ▼
┌─────────────────────────┐
│  Nginx (Web Server)     │  - Reverse proxy to PHP-FPM
│                         │  - Serves static assets directly
│                         │  - SSL (if no Cloudflare)
│                         │  - Gzip/Brotli compression
└─────────┬───────────────┘
          │
          ▼
┌─────────────────────────┐
│  PHP-FPM 8.3            │  - Executes Laravel 11 application
│  (Laravel Application)  │  - Single process serves BOTH:
│  [ADR-001]              │    - Public website (anonymous)
│                         │    - CMS (authenticated)
│                         │    - Operational system (existing)
└─────┬──────────┬────────┘
      │          │
      ▼          ▼
┌──────────┐ ┌──────────────────┐
│  MariaDB │ │  Local Storage    │
│  [ADR-006]│ │  [ADR-009]        │
│           │ │                   │
│  Single   │ │  storage/app/     │
│  database │ │  public/          │
│           │ │  (storage:link)   │
│  Website  │ │                   │
│  tables + │ │  Media variants,  │
│  Op tables│ │  gallery, uploads │
│  (logical │ │                   │
│   separa- │ │                   │
│   tion)   │ │                   │
└──────────┘ └──────────────────┘

┌──────────────────────────────────────────────────────────┐
│  QUEUE WORKER (optional, can run on same server)         │
│  - Processes email notification jobs (inquiry)           │
│  - Can use sync driver for MVP if server is single-role  │
└──────────────────────────────────────────────────────────┘
```

### 15.2 Environments

| Environment | Purpose | ADR/AR |
|-------------|---------|--------|
| Local/Dev | Developer machine; local DB, local storage | AR-18 |
| Staging | Pre-production validation; sensitive-data leak test; responsive QA | AR-18, PRD §20.4 |
| Production | Live public website + CMS + operational system | ADR-001 |

### 15.3 Release Strategy

| Aspect | Strategy | ADR/AR |
|--------|----------|--------|
| Deploy artifact | Single codebase deploy (ADR-001) | ADR-001 |
| Feature flags | Website features can ship dark via flags | AR-18 |
| Migration gating | DB migrations gated by PRD hard gates (§20.4) | PRD |
| Sensitive data gate | Automated leak test must pass in staging before prod deploy | ADR-005, PRD §20.4 |
| Rollback | Standard Laravel deploy rollback (previous release artifact) | AR-18 |

### 15.4 Backup

| Asset | Frequency | Retention | ADR/AR |
|-------|-----------|-----------|--------|
| MariaDB database | Weekly (minimum) | Per ops policy | ADR-006, AR-25 |
| Local media (`storage/app/public`) | Weekly (minimum) | Per ops policy | ADR-009, AR-25 |
| Restore verification | Periodic | — | AR-25 |

---

## 16. Scalability Strategy

**References: ADR-010 (Future Multi-Business Ready Architecture), ADR-002 (Modular Monolith), AR-17 (Caching)**

### 16.1 Adding Future Business Units

The architecture is **structurally ready** for future Jaya Sakti business units without any architecture change (ADR-010). A new unit is added as a **sibling module** under `Modules/`.

```
app/Modules/
├── Website/
│   └── Jsl/                    ← Jaya Sakti Line (MVP — exists now)
│       ├── Http/Controllers/
│       ├── Filament/
│       ├── Models/
│       └── ...
│
├── Website/
│   └── Jss/                    ← JSS Website (future Phase 5)
│       ├── Http/Controllers/
│       ├── Filament/
│       ├── Models/
│       └── ...                 ← Added as sibling; no kernel change
│
└── Website/
    └── Group/                  ← Group Website (future Phase 5)
        └── ...                 ← Added as sibling; no kernel change
```

**What changes when a new unit is added:**
- New module directory under `Modules/`
- New Filament panel (if the unit has its own CMS) or new resources in existing panel
- New route file with unit-specific prefix (`jss.public.*`)
- New database tables with unit-specific naming prefix
- New brand configuration

**What does NOT change:**
- Platform Kernel (auth, media abstraction, cache, queue, config, logging)
- ADR-002 dependency rules (each unit is isolated; no cross-unit imports)
- Single database (ADR-006) — new tables are added, not a new database
- Single deploy (ADR-001) — new unit ships through the same pipeline
- Public projection pattern (ADR-005) — applies to any unit with sensitive data

### 16.2 Adding Future Functional Modules (Broker, CRM, Owner Portal)

| Future Module | Phase | How Added | What Changes in Architecture? |
|---------------|-------|-----------|-------------------------------|
| Broker Module (inquiry pipeline, CRM sync, assignment) | Phase 2-3 | New module under `Modules/Broker/`; hooks into inquiry pipeline extension point | Nothing — inquiry pipeline seam already documented |
| Owner Self-Service Portal | Phase 3 | New module under `Modules/OwnerPortal/`; new auth surface (owner accounts) | New guard/role; no change to existing ADRs |
| Trust & Marketplace Features | Phase 4 | Extensions within existing modules | Additive |
| Operational Integration | Phase 6 | Integration port (interface) between website and operational module | New ADR required (per supersession policy); no MVP change |

### 16.3 Traffic Scalability

| Concern | MVP Strategy | Future Enhancement | ADR/AR |
|---------|-------------|-------------------|--------|
| Public page performance | Response caching with tag-based invalidation | CDN in front of app (Cloudflare) | AR-17 |
| Image delivery | Local disk + responsive variants + lazy load | S3/CDN via media abstraction config swap | ADR-009 |
| Database load | Cached public pages minimize DB hits | Read replica if needed | ADR-006 |
| Queue (email) | Sync or single worker | Multiple workers / Redis queue | AR-15 |

> **Key principle (ADR-010):** All scalability enhancements are **infrastructure/config changes**, not architecture changes. The modular monolith scales by tuning its deployment, not by splitting its codebase.

---

## 17. Quality Attributes

**References: All ADRs, PRD §7 (NFRs), PRD §17 (Acceptance Criteria), AR-17, AR-21, AR-23**

| Quality Attribute | How Addressed | Target | Governing ADR/AR |
|-------------------|--------------|--------|-------------------|
| **Security** | Public Projection Pattern (ADR-005); CMS auth without branch scoping (ADR-003); policy-based authorization (ADR-008); CSRF + spam protection (AR-12); HTTPS enforced; EXIF stripping (ADR-009) | AC-4: zero sensitive data leakage; AC-14: HTTPS + rate-limited login | ADR-003, ADR-005, ADR-008, ADR-009 |
| **Maintainability** | Modular monolith with clear boundaries (ADR-002); CMS-driven content (no deploy for content changes); policy-based auth; media abstraction; platform kernel vs unit ownership documented | Content updates < 1 min; no code deploy for content (PRD AC-10) | ADR-002, ADR-008, ADR-009, ADR-010 |
| **Performance** | Server-side Blade rendering; response caching with tag-based invalidation (AR-17); responsive images with lazy load; EXIF-stripped optimized variants | LCP < 2.5s desktop, < 3s 4G mobile (PRD AC-8) | ADR-009, AR-04, AR-17 |
| **Scalability** | Structural readiness for future units (ADR-010); single DB with logical separation (ADR-006); media abstraction for S3 swap (ADR-009); cache for public traffic (AR-17) | Add new business unit = additive module, no rewrite | ADR-006, ADR-009, ADR-010, AR-17 |
| **Testability** | Module isolation (ADR-002); thin controllers + services + actions; automated sensitive-data leak test parameterized over all public routes (AR-23); PHPUnit (existing) | Leak test must pass before launch (PRD §20.4) | ADR-002, ADR-005, AR-23 |
| **Availability** | Single application (ADR-001); uptime ≥ 99% (PRD NFR); weekly backups (AR-25); response caching reduces DB dependency | ≥ 99% uptime | ADR-001, AR-17, AR-25 |
| **Localization-readiness** | Laravel i18n for UI strings; nullable EN content fields; ID default (AR-24) | ID default; EN structurally ready if approved | AR-24 |
| **Accessibility** | WCAG 2.1 AA basics: alt text, contrast, keyboard navigation (PRD NFR) | Alt text must not leak sensitive data (ADR-005) | ADR-005, PRD NFR |

---

## 18. Architecture Constraints

**References: ADR-002, ADR-004, ADR-005, ADR-006, ADR-007, ADR-008, ADR-010**

These are **hard rules** that developers must NOT violate. Each is derived from an Accepted ADR. Violating a constraint is an architecture violation and must be blocked in code review.

### Forbidden Actions

| # | Constraint | Rationale | ADR |
|---|-----------|-----------|-----|
| C-1 | ❌ Never expose the operational `Vessel` model in the website module. | Marketing and operational domains are separate bounded contexts. | ADR-004 |
| C-2 | ❌ Never bypass the Projection Layer for public-facing vessel data. | Sensitive fields must be structurally unreachable from public code paths. | ADR-005 |
| ❌ Never pass the full VesselListing entity (with sensitive fields) to a Blade view in a public context. | Same as C-2; the view must only receive a projection. | ADR-005 |
| C-4 | ❌ Never place CMS logic inside public website components. | Public and CMS surfaces are separated by route groups and middleware. | ADR-007 |
| C-5 | ❌ Never import operational namespaces, services, models, or Filament resources into the website module. | Module isolation rule; prevents scope creep. | ADR-002 |
| C-6 | ❌ Never import website module namespaces into the operational module. | Module isolation is bidirectional. | ADR-002 |
| C-7 | ❌ Never apply `ScopeByBranch` middleware to the CMS panel. | Marketing content is not branch-scoped. | ADR-003 |
| C-8 | ❌ Never use inline `if (admin)` / `if (role)` checks for authorization. | All authorization goes through Eloquent Policies + Spatie Permission. | ADR-008 |
| C-9 | ❌ Never store sensitive media (with owner/identity data) on the public disk. | Sensitive media stays on non-public disk; EXIF stripped on all uploads. | ADR-009, ADR-005 |
| C-10 | ❌ Never create foreign keys or joins between website module tables and operational module tables. | Single database with logical separation; no cross-domain data coupling. | ADR-006 |
| C-11 | ❌ Never hardcode "Jaya Sakti Line" brand assumptions in the Platform Kernel. | Kernel must remain unit-agnostic for future multi-business support. | ADR-010 |
| C-12 | ❌ Never build multi-tenant selection, per-unit admin switching, or per-unit database routing in the MVP. | These are speculative future features; structural readiness only. | ADR-010 |
| C-13 | ❌ Never render sensitive fields (vessel name, IMO, owner, certificates, price) in any public HTML, API response, alt text, or downloadable asset. | PRD AC-4 hard acceptance criterion. | ADR-005, PRD AC-4 |
| C-14 | ❌ Never store EXIF/metadata in uploaded images without stripping. | Prevents owner/vessel identity leakage via image metadata. | ADR-005, ADR-009 |
| C-15 | ❌ Never introduce a new architecture decision without an ADR. | ADRs are the constitution; new decisions require a new ADR approved by the ARB. | ADR-001 (Supersession Policy) |

### Required Actions

| # | Constraint | Rationale | ADR |
|---|-----------|-----------|-----|
| R-1 | ✅ All public vessel data access must go through a public projection. | Structural enforcement of AC-4. | ADR-005 |
| R-2 | ✅ All CMS authorization must go through Eloquent Policies. | Future-proof authorization; no inline checks. | ADR-008 |
| R-3 | ✅ All uploaded images must be EXIF-stripped before storage. | Privacy/sensitive-data protection. | ADR-009, ADR-005 |
| R-4 | ✅ All uploaded images must have responsive variants generated. | Performance (AC-8); mobile-first. | ADR-009 |
| R-5 | ✅ Website module tables must use a naming prefix to distinguish from operational tables. | Logical separation in single database. | ADR-006 |
| R-6 | ✅ Public routes must be anonymous (no auth middleware). | Public website is anonymous. | ADR-007 |
| R-7 | ✅ CMS routes must be authenticated and role-gated (CMS role only). | Admin surface protection. | ADR-003, ADR-008 |
| R-8 | ✅ Inquiry form submissions must dispatch email notifications via the queue. | Non-blocking submission; reliable notification. | AR-15 |
| R-9 | ✅ The automated sensitive-data leak test must run in CI and pass before merge. | PRD §20.4 hard gate. | ADR-005, AR-23 |
| R-10 | ✅ Media access must go through the media abstraction, not raw filesystem calls. | Future S3/CDN swap without code rewrite. | ADR-009 |

---

## 19. Implementation Guidelines

**References: All ADRs, PRD §20 (Development Recommendation), PRD §20.4 (Hard Quality Gates)**

### Developer Checklist Before Coding

Every developer must verify the following before writing any code in the website module:

#### Module Boundary
- [ ] Is this code placed within the website module namespace (`Modules/Website/Jsl/`)?
- [ ] Does this code import any operational namespace? If yes — **stop; ADR-002 violation**.
- [ ] Does this code place CMS logic in a public component? If yes — **stop; ADR-007 violation**.

#### Domain Model
- [ ] Am I using the marketing `VesselListing` entity, not the operational `Vessel`? (ADR-004)
- [ ] Am I creating any foreign key or join to an operational table? If yes — **stop; ADR-006 violation**.

#### Projection Layer (ADR-005)
- [ ] Does this public controller/action receive a full entity? If yes — **stop; must use projection**.
- [ ] Does this Blade view have access to sensitive fields? If yes — **stop; must receive projection only**.
- [ ] Am I passing the full entity to a view "just this once"? If yes — **stop; no exceptions**.

#### Authorization (ADR-008)
- [ ] Am I using an inline `if (admin)` check? If yes — **stop; use a Policy**.
- [ ] Does this CMS resource have a registered Eloquent Policy?
- [ ] Am I applying `ScopeByBranch` to the CMS panel? If yes — **stop; ADR-003 violation**.

#### Media (ADR-009)
- [ ] Does this upload path go through the media abstraction? If no — **stop**.
- [ ] Is EXIF stripped before storage? If no — **stop; ADR-005/ADR-009 violation**.
- [ ] Are responsive variants generated? If no — **stop; ADR-009 violation**.
- [ ] Is the filename obfuscated (non-guessable)? If no — **stop; ADR-009 violation**.
- [ ] Is sensitive media on a non-public disk? If no — **stop; ADR-009 violation**.

#### Routing (ADR-007)
- [ ] Does this public route have auth middleware? If yes — **stop; public routes are anonymous**.
- [ ] Does this route name use the `jsl.` prefix? If no — **fix; collision risk**.
- [ ] Is this CMS route under the dedicated CMS panel? If no — **stop; ADR-003 violation**.

#### Security (ADR-005)
- [ ] Does this alt text contain vessel name, IMO, or owner? If yes — **stop; AC-4 violation**.
- [ ] Does this API response include sensitive fields? If yes — **stop; use projection**.
- [ ] Does this form have CSRF protection? If no — **stop; AR-12 violation**.
- [ ] Does the inquiry form have spam protection (honeypot/rate-limit)? If no — **add it**.

#### Testing (AR-23)
- [ ] Have I written a feature test for this public page?
- [ ] If this involves vessel data, does the leak test cover this new route?
- [ ] Does the leak test assert absence of: vessel name, IMO, owner, certificates?

#### Future-Readiness (ADR-010)
- [ ] Am I hardcoding "Jaya Sakti Line" brand text in the Platform Kernel? If yes — **stop; ADR-010 violation**.
- [ ] Am I building multi-tenant selection or per-unit routing? If yes — **stop; speculative future feature**.

#### Quality Gates (PRD §20.4)
- [ ] Sensitive data leakage test passes?
- [ ] Responsive QA signed off (mobile, tablet, desktop)?
- [ ] Security checklist complete (HTTPS, auth, CSRF, throttle, spam)?
- [ ] Stakeholder content reviewed and approved?

---

## 20. Architecture Traceability Matrix

**References: PRD-001 (Frozen), ADR-001 through ADR-010**

This matrix maps every PRD requirement to its governing ADR(s) and the architecture component that implements it. It ensures every requirement has a traceable architectural implementation.

| PRD Requirement | PRD Ref | Governing ADR | Architecture Component |
|----------------|---------|---------------|------------------------|
| Public Company Profile (About, Overview, Vision, Mission) | FR-01, §14.1 | ADR-007, ADR-008 | Company Component (public) + Company Profile Editor (CMS Filament) + Content Domain |
| Services Display (CMS-managed, show/hide) | FR-02, §14.2 | ADR-007, ADR-008 | Services Component (public) + Services Manager (CMS Filament) + Content Domain |
| Vessel Trading public listing (general info, ≤6 images, Open/Closed) | FR-03, §15 | ADR-004, ADR-005, ADR-007 | Trading Component (public) + Vessel Listings Manager (CMS) + Marketing Domain + Projection Layer |
| Vessel detail page | FR-03 | ADR-004, ADR-005, ADR-007 | Trading Component → VesselDetailController → Projection Layer → Blade view |
| Inquiry — WhatsApp per vessel | FR-04, §16.1 | ADR-007, AR-15 | Inquiry Component (client-side wa.me link generation) + GA4 event |
| Inquiry — Email per vessel | FR-04, §16.1 | ADR-007, AR-15 | Inquiry Component (client-side mailto link) + GA4 event |
| Inquiry — Form per vessel | FR-04, §16.1 | ADR-007, AR-15 | InquiryController → SubmitInquiryAction → Inquiry Domain → Queue (email) |
| General Contact | FR-05, §16.2 | ADR-007 | Contact Component (public) + same inquiry channels (vessel reference nullable) |
| Gallery (CMS-managed) | FR-06, §14.3 | ADR-007, ADR-008, ADR-009 | Gallery Component (public) + Gallery Manager (CMS) + Content Domain + Media Domain |
| CMS Authentication (single admin, secure login) | FR-07, §13 | ADR-003, ADR-008 | CMS Filament Panel + `web` guard + Spatie Permission (CMS role) + EnsurePanelRole (no ScopeByBranch) |
| CMS Vessel Listing Management (CRUD, ≤6 images, ordering, status toggle) | FR-08, §13 | ADR-003, ADR-008, ADR-009 | Vessel Listings Manager (Filament Resource) + Marketing Domain + Media Service |
| Sensitive fields stored internally, never public | FR-08, §15.2, AC-4 | ADR-004, ADR-005 | Marketing Domain (full entity) + Projection Layer (public) + Automated leak test |
| CMS Inquiry Inbox (view, search/filter) | FR-09, §13 | ADR-003, ADR-008 | Inquiry Inbox (Filament Resource) + Inquiry Domain |
| Mark inquiry read/contacted/archived | FR-09, §13 | ADR-008 | Inquiry Inbox (Filament) + Inquiry Domain (status field) + Policy |
| Email notification on new inquiry | FR-09, §16.3 | AR-15 | Queue + Mail (SMTP) → broker inbox |
| Auto-reply to submitter | §16.3 | AR-15 | Queue + Mail (SMTP) → submitter email |
| SEO-friendly URLs | FR-10 | ADR-007 | Public route group (clean URLs: /vessels, /about, /services) |
| Open Graph + meta per page/listing | FR-10 | ADR-007 | Blade view head section (per-page meta) |
| Sitemap.xml auto-generated | FR-10 | ADR-007 | Laravel sitemap generation (scheduled) |
| Responsive (mobile, tablet, desktop) | FR-11, AC-1 | ADR-007 | Blade responsive design + responsive image variants (ADR-009) |
| Mobile-first design | FR-11 | ADR-007, AR-04 | Server-side Blade (mobile-first CSS) |
| No sensitive data in HTML/responses/images/alt | AC-4, §15.2 | ADR-005 | Projection Layer + EXIF stripping + automated leak test + alt text review |
| Open vessels accept inquiries; Closed clearly marked | AC-5 | ADR-004, ADR-007 | Marketing Domain (status field) + Trading Component (status badge + inquiry CTA gating) |
| HTTPS enforced | AC-14, NFR | ADR-007 | Deployment (Nginx/Cloudflare SSL) |
| Admin routes protected; login rate-limited | AC-14, NFR | ADR-003, ADR-008 | CMS Panel middleware stack + Filament login throttle |
| CSRF + spam protection on inquiry form | NFR | AR-12 | Web middleware (CSRF) + honeypot + rate limit |
| Consent checkbox (privacy) | NFR, §16.1 | ADR-007 | Inquiry form (Blade) + validation |
| GA4 + conversion events | NFR, §3.3 | ADR-007 | Client-side GA4 tag + dataLayer events on inquiry CTAs |
| CMS-driven content (no deploy) | NFR, AC-10 | ADR-003, ADR-008 | Filament CMS (full CRUD for all content entities) |
| Rich-text editor (headings, bold, lists, images) | §14.1 | ADR-008 | Filament RichEditor field + HTML sanitizer (allow-list) |
| Image upload max size, auto-resize, obfuscated paths | §14.5 | ADR-009 | Media Service (upload pipeline: EXIF strip → resize → obfuscated store) |
| Soft-delete for vessels & inquiries | §13 | ADR-004 | Eloquent softDeletes on marketing/inquiry models |
| Audit log of admin actions | §13 | ADR-008, AR-21 | Spatie Activity Log (existing infra) |
| Weekly DB + media backup | NFR, §16.4 | ADR-006, ADR-009, AR-25 | Backup strategy (ops) |
| Inquiry retention ≥ 12 months | §16.4 | ADR-006 | Inquiry Domain (retention policy / soft-delete) |
| Multi-business structural readiness | BO-6, Phase 5 | ADR-010 | Platform Kernel vs Unit module structure; no hardcoded brand in kernel |
| No operational integration in MVP | §8.2, Phase 6 | ADR-002, AR-16 | Module isolation rule; integration port (documented seam, not implemented) |

---

## 21. Glossary

| Term | Definition | Source |
|------|-----------|--------|
| **ADR** | Architecture Decision Record. An immutable record of an approved architecture decision. | ADR-001 (Supersession Policy) |
| **ARD** | Architecture Design Document. The implementation blueprint derived from accepted ADRs. | This document |
| **PRD** | Product Requirements Document. The frozen product scope. | PRD-001 |
| **Modular Monolith** | An architecture style where the application is organized as modules within a single deployable unit, without distributed-system complexity. | ADR-002 |
| **Platform Kernel** | The shared infrastructure layer (auth, media abstraction, cache, queue, config, logging) that is unit-agnostic and shared across all business unit modules. | ADR-002, ADR-010 |
| **Business Unit Module** | A self-contained module representing one Jaya Sakti business unit (e.g., Jaya Sakti Line). Owns its own domain, controllers, Filament resources, and routes. | ADR-010 |
| **Public Projection** | A read shape that structurally excludes sensitive fields from public-facing code paths. | ADR-005 |
| **Sensitive Fields** | Vessel real name, IMO number, owner/ownership details, full certificates, and price/commercial terms. Never exposed publicly. | PRD §15.2, ADR-005 |
| **Marketing Domain** | The bounded context for vessel trading listings. Distinct from the operational logistics domain. | ADR-004 |
| **Operational Domain** | The existing bounded context for logistics (shipment, voyage, vessel plan). Out of scope for the website MVP. | ADR-002, ADR-004 |
| **CMS Panel** | The dedicated Filament panel for managing website content. The 4th panel in the application. | ADR-003 |
| **Public Surface** | The anonymous, server-rendered website accessible to internet users. | ADR-007 |
| **CMS Surface** | The authenticated, Filament-powered admin interface for content management. | ADR-007 |
| **Media Abstraction** | A service/interface that encapsulates all filesystem and image-processing concerns, enabling future S3/CDN swap via configuration. | ADR-009 |
| **EXIF Stripping** | Removal of EXIF/metadata from uploaded images to prevent owner/vessel identity leakage. | ADR-005, ADR-009 |
| **Responsive Variants** | Multiple sizes of an image generated on upload to support responsive `srcset` and lazy loading. | ADR-009 |
| **Leak Test** | An automated, parameterized test that asserts the absence of sensitive fields across all public routes/responses. | ADR-005, AR-23 |
| **Integration Port** | A documented seam (interface/naming convention) for future integration with the operational system. Not implemented in MVP. | ADR-010, AR-16 |
| **ScopeByBranch** | An existing middleware that scopes operational data by branch. Explicitly excluded from the CMS panel. | ADR-003 |
| **EnsurePanelRole** | An existing middleware that checks a user's role for panel access. Extended for the CMS role. | ADR-003, ADR-008 |
| **Change Request (CR)** | The formal process for modifying frozen PRD scope. Required before any scope-affecting change. | PRD-001 (Scope Control) |

---

**End of ARD-001 — Architecture Design Document.**

This document is the official implementation blueprint for the Jaya Sakti Line Website MVP. It derives solely from accepted ADRs and the frozen PRD-001. It introduces no new architecture decisions. The next document is **DBD-001 — Database Design Document**, which will define the data model, table structure, naming conventions, and migration plan consistent with the ADRs and this ARD.
