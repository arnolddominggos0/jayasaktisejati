# Database Design Document (DBD)
## DBD-001 — Jaya Sakti Line Website MVP
### Official Database Blueprint

**Project:** Jaya Sakti App
**Document:** DBD-001 — Database Design Document
**Version:** 1.1.0
**Status:** ✅ APPROVED — Database Blueprint
**Document Owner:** Data Architecture Team
**Last Updated:** 2026-06-30
**Next Phase:** UX-001 — UI/UX Specification
**Sources of Truth (in precedence order):**
1. `docs/03-architecture/ADR-001.md` (v1.0.0, Accepted) — **Architectural Constitution; highest authority**
2. `docs/03-architecture/ARD-001-JSL-Website-MVP.md` (v1.0.0, Approved) — **Implementation Blueprint**
3. `docs/03-architecture/ARCHITECTURE_REVIEW.md` (v0.1.0, Accepted by ARB)
4. `docs/02-product/PRD-001-JSL-Website-MVP.md` (v1.1.0, Frozen)
5. `docs/02-product/CR-001-001-Internal-Vessel-Certificate-Management.md` (v1.0.1, Approved) — added `jsl_vessel_certificates`

> **Governance Rule**
> This document introduces **no new architecture decisions** and **no new business requirements**. It transforms the approved architecture (ADR + ARD) into a database design. If any detail conflicts with an Accepted ADR, the **ADR always wins**. If any detail conflicts with the ARD, the **ARD wins** over this document.
>
> **Prohibited in this document:** Laravel migrations, Eloquent models, seeders, factories, SQL statements, Filament resources, and any executable code. This is **database design only**.

---

## Change History

| Version | Date | Author | Description |
|---------|------|--------|-------------|
| 1.0.0 | 2026-06-30 | Data Architecture Team | Initial DBD — database blueprint derived from ADR-001 through ADR-010 and ARD-001. |
| 1.1.0 | 2026-06-30 | Data Architecture Team | Added `jsl_vessel_certificates` entity per approved [CR-001-001](../02-product/CR-001-001-Internal-Vessel-Certificate-Management.md) (Internal Vessel Certificate Management). Superseded the free-text `certificates` column on `jsl_vessel_listings`. No new architecture decision; follows the existing `VesselImage` composition pattern. |

---

## Table of Contents

1. Document Information
2. Database Design Goals
3. Traceability (PRD → ADR → ARD → Database Entity)
4. Business Domains
5. Domain Entities
6. Aggregate Design
7. Relationship Design
8. Entity Lifecycle
9. Constraints
10. Naming Convention
11. Normalization Review
12. Logical ERD
13. Physical Data Model
14. Media Strategy
15. Security Classification
16. Future Extension Strategy
17. Performance Considerations
18. Developer Guidelines
19. Database Traceability Matrix
20. Glossary

---

## Design Process Followed

This document was produced by following the mandated design process in order. No step was skipped.

```
Step 1:  Business Domain         → §4
Step 2:  Entities                 → §5
Step 3:  Relationships            → §7
Step 4:  Aggregate Roots          → §6
Step 5:  Value Objects            → §5 (within entity descriptions)
Step 6:  Entity Lifecycle         → §8
Step 7:  Constraints              → §9
Step 8:  Normalization            → §11
Step 9:  Logical Data Model       → §12
Step 10: Physical Data Model      → §13
```

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
| Table Prefix | `jsl_` (Jaya Sakti Line unit) |
| Predecessor Documents | PRD-001 (Frozen), Architecture Review (Accepted), ADR-001 (Accepted), ARD-001 (Approved), Architecture Walkthrough (Approved with Notes) |
| Successor Document | UX-001 — UI/UX Specification |
| Authority | ADR > ARD > DBD. This document must not contradict any Accepted ADR or the approved ARD. |

---

## 2. Database Design Goals

| # | Goal | Source |
|---|------|--------|
| G-1 | **Isolate the marketing domain** — all website tables are logically separated from operational tables by the `jsl_` prefix. No foreign keys cross the marketing/operational boundary. | ADR-006 (Single Database), ARD §18 R-5 |
| G-2 | **Protect sensitive data at the data level** — sensitive vessel fields (real name, IMO, owner, certificates, price) are stored in the same table but classified, and the public projection pattern ensures they are never selected for public contexts. | ADR-005 (Public Projection Pattern), ARD §10.3 |
| G-3 | **Support CMS-driven content** — all public-facing content (company profile, services, vessel listings, gallery) is stored in database tables managed via the CMS, not hardcoded. | ADR-003, ADR-008, PRD FR-01/FR-02/FR-03/FR-06 |
| G-4 | **Enforce media limits** — maximum 6 images per vessel listing, with ordering and thumbnail designation, enforced at the application layer and constrained at the data level. | PRD §15.4, ADR-009, ARD §11.3 |
| G-5 | **Support i18n readiness** — nullable English (EN) content fields are included for entities with CMS-managed text, so enabling EN later is a data-population task, not a schema migration. | AR-24, Architecture Walkthrough Note N-6 |
| G-6 | **Preserve future extensibility** — the schema is structured so future business units (JSS, Group, Shipbroker) can add their own prefixed tables without modifying JSL tables or the shared platform kernel. | ADR-010 (Multi-Business Ready), ARD §16 |
| G-7 | **Maintain auditability** — all entities carry standard audit fields (created_at, updated_at) and most support soft deletion (deleted_at) for recoverability. | PRD §13, AR-21, AR-25 |
| G-8 | **Reuse existing auth infrastructure** — the CMS uses the existing `users` table and Spatie Permission tables. No new auth tables are created. | ADR-003, ADR-008, AR-10 |

---

## 3. Traceability

**Map: PRD Requirement → ADR → ARD Component → Database Entity**

| PRD Requirement | ADR | ARD Component | Database Entity (Table) |
|----------------|-----|---------------|------------------------|
| FR-01 Company Profile (About, Overview, Vision, Mission) | ADR-007, ADR-008 | Company Component + Company Profile Editor + Content Domain | `jsl_company_profiles` |
| FR-02 Services Display | ADR-007, ADR-008 | Services Component + Services Manager + Content Domain | `jsl_services` |
| FR-03 Vessel Trading (general info, ≤6 images, Open/Closed) | ADR-004, ADR-005, ADR-007 | Trading Component + Vessel Listings Manager + Marketing Domain + Projection Layer | `jsl_vessel_listings`, `jsl_vessel_images` |
| FR-04 Inquiry per vessel (WhatsApp, Email, Form) | ADR-007, AR-15 | Inquiry Component + InquiryController + SubmitInquiryAction | `jsl_inquiries` (form only; WA/Email are client-side, no record) |
| FR-05 General Contact | ADR-007 | Contact Component + same inquiry channels | `jsl_inquiries` (vessel_listing_id = NULL) |
| FR-06 Gallery | ADR-007, ADR-008, ADR-009 | Gallery Component + Gallery Manager + Content Domain + Media Domain | `jsl_gallery_items`, `jsl_media_assets` |
| FR-07 CMS Authentication | ADR-003, ADR-008 | CMS Panel + web guard + Spatie Permission | Existing `users` table + Spatie tables (no new tables) |
| FR-08 CMS Vessel Listing Management | ADR-003, ADR-008, ADR-009 | Vessel Listings Manager + Marketing Domain + Media Service | `jsl_vessel_listings`, `jsl_vessel_images`, `jsl_media_assets` |
| FR-09 CMS Inquiry Inbox | ADR-003, ADR-008 | Inquiry Inbox + Inquiry Domain | `jsl_inquiries` |
| FR-10 SEO & Sharing | ADR-007 | Public route group + Blade head section | `jsl_vessel_listings` (public_ref_code for URLs; marketing_description for meta) |
| FR-11 Performance & Responsive | ADR-007, ADR-009 | Blade responsive + responsive image variants | `jsl_media_assets` (variant path columns) |
| Sensitive fields never public (AC-4) | ADR-004, ADR-005 | Marketing Domain + Projection Layer + Leak test | `jsl_vessel_listings` (sensitive columns classified; projection excludes them) |
| Soft-delete for vessels & inquiries | — | ARD §18 | `jsl_vessel_listings`, `jsl_inquiries` (deleted_at column) |
| Audit log of admin actions | ADR-008, AR-21 | Spatie Activity Log | Existing Spatie `activity_log` table (no new tables) |
| Inquiry retention ≥ 12 months | — | ARD §8.2 Inquiry Domain | `jsl_inquiries` (retention policy applied to soft-deleted records) |
| Marketing-editable site settings | AR-22 | ARD §14.2 Configuration Strategy | `jsl_site_settings` |
| CMS-stored settings (social links, brand) | AR-22 | ARD §14.2 | `jsl_site_settings` |
| i18n readiness (nullable EN fields) | AR-24 | ARD §17 Quality Attributes | All content-bearing tables (nullable `_en` columns) |
| FR-12 CMS Vessel Certificate Management *(CR-001-001)* | ADR-004, ADR-005, ADR-009 (existing — no new ADR) | Trading Component + Vessel Listings Manager (Certificates tab, UX-001 §12.6a) + Marketing Domain + Projection Layer | `jsl_vessel_certificates` |

---

## 4. Business Domains

**Reference: ARD §8 (Domain Architecture)**

The database serves five business domains within the Website Module. Each domain owns its own tables, all prefixed with `jsl_`.

| # | Domain | ARD Reference | Purpose | Tables Owned |
|---|--------|---------------|---------|--------------|
| D-1 | **Content Domain** | ARD §8.2 | CMS-driven website content: company profile, services, gallery items. | `jsl_company_profiles`, `jsl_services`, `jsl_gallery_items`, `jsl_site_settings` |
| D-2 | **Marketing Domain** | ARD §8.2 | Vessel trading listings — the core marketing offering. Contains both public (general info) and sensitive (internal) fields. | `jsl_vessel_listings`, `jsl_vessel_images`, `jsl_vessel_certificates` |
| D-3 | **Inquiry Domain** | ARD §8.2 | Form-based inquiry records from public visitors. | `jsl_inquiries` |
| D-4 | **Media Domain** | ARD §8.2 | Media asset metadata (file paths, variants, dimensions). Shared reference entity for all domains that use images. | `jsl_media_assets` |
| D-5 | **Projection Layer** | ARD §8.2, ADR-005 | Cross-cutting, not a data-storage domain. It is an application-layer concept that governs **which columns** are selected from `jsl_vessel_listings` for public contexts. No tables owned. | (no tables — application-layer pattern) |

**Domain isolation rule (ADR-002, ADR-006):** No table in any `jsl_` domain has a foreign key referencing an operational table, and no operational table references a `jsl_` table. The domains are isolated within the single MariaDB database by naming convention and application-layer dependency rules.

---

## 5. Domain Entities

### 5.1 Content Domain Entities

#### 5.1.1 CompanyProfile
- **Purpose:** Stores the single company profile content displayed on the public About page.
- **Ownership:** Content Domain (Website Module / JSL unit).
- **Lifecycle:** Singleton — one row. Created once, updated as needed. No deletion.
- **Type classification:** Singleton aggregate root.
- **PRD reference:** FR-01, §14.1.
- **ARD reference:** ARD §8.2 Content Domain, ARD §6 Company Component.

| Field Group | Description |
|------------|-------------|
| Identity | Single row (id = 1 convention). |
| Content (ID default) | about, overview, vision, mission — rich text. |
| Content (EN, nullable) | about_en, overview_en, vision_en, mission_en — rich text, nullable. Populated only if EN toggle is approved (AR-24). |
| Audit | created_at, updated_at. |

#### 5.1.2 Service
- **Purpose:** Represents one service offered by Jaya Sakti Line, displayed on the public Services page.
- **Ownership:** Content Domain.
- **Lifecycle:** Created → Visible/Hidden (toggled via `is_visible`) → Soft-deleted.
- **Type classification:** Aggregate root.
- **PRD reference:** FR-02, §14.2.
- **ARD reference:** ARD §6 Services Component, ARD §8.2 Content Domain.

| Field Group | Description |
|------------|-------------|
| Identity | Surrogate primary key. |
| Content (ID default) | title, description (rich text). |
| Content (EN, nullable) | title_en, description_en — nullable (AR-24). |
| Media | Optional icon/image reference via `media_asset_id` FK to `jsl_media_assets`. |
| Display control | is_visible (boolean), sort_order (integer). |
| Audit | created_at, updated_at, deleted_at (soft delete). |

#### 5.1.3 GalleryItem
- **Purpose:** Represents one image in the public gallery, with optional caption and category.
- **Ownership:** Content Domain.
- **Lifecycle:** Created → Soft-deleted.
- **Type classification:** Aggregate root (references MediaAsset).
- **PRD reference:** FR-06, §14.3.
- **ARD reference:** ARD §6 Gallery Component, ARD §8.2 Content Domain, ARD §11.3.

| Field Group | Description |
|------------|-------------|
| Identity | Surrogate primary key. |
| Media | media_asset_id FK to `jsl_media_assets`. |
| Content (ID default) | caption (nullable), category (nullable). |
| Content (EN, nullable) | caption_en — nullable (AR-24). |
| Display control | sort_order (integer). |
| Audit | created_at, updated_at, deleted_at (soft delete). |

#### 5.1.4 SiteSettings
- **Purpose:** Stores marketing-editable site-wide settings (social links, displayed contact info, brand text). Distinct from `.env` secrets (broker email, broker WhatsApp, GA4 ID, SMTP credentials — those are environmental, per AR-22).
- **Ownership:** Content Domain.
- **Lifecycle:** Singleton — one row. Created once, updated as needed. No deletion.
- **Type classification:** Singleton aggregate root.
- **PRD/ARD reference:** AR-22, ARD §14.2 Configuration Strategy.

| Field Group | Description |
|------------|-------------|
| Identity | Single row (id = 1 convention). |
| Brand (ID default) | site_name, tagline (nullable), footer_text (nullable). |
| Brand (EN, nullable) | site_name_en, tagline_en, footer_text_en — nullable (AR-24). |
| Contact display | contact_address (nullable), contact_phone_display (nullable), contact_email_display (nullable). |
| Social links | social_facebook_url, social_instagram_url, social_linkedin_url (all nullable). |
| Audit | created_at, updated_at. |

> **Note (AR-22):** The broker WhatsApp number and broker email used in inquiry routing are stored in `.env`, not in `jsl_site_settings`. The `contact_phone_display` and `contact_email_display` fields in SiteSettings are for **public display** purposes only (e.g., showing a general contact number in the footer) and may or may not match the `.env` routing values. This separation keeps secrets out of the database.

### 5.2 Marketing Domain Entities

#### 5.2.1 VesselListing
- **Purpose:** Represents one vessel trading opportunity posted by Jaya Sakti Line. This is the **core entity** of the marketing domain. Contains both public (general information) fields and sensitive (internal-only) fields.
- **Ownership:** Marketing Domain (Website Module / JSL unit).
- **Lifecycle:** Created (default status: Open) → Open → Closed → Soft-deleted. See §8 for full lifecycle.
- **Type classification:** Aggregate root.
- **PRD reference:** FR-03, FR-08, §15 (Vessel Trading Requirements).
- **ARD reference:** ARD §8.2 Marketing Domain, ADR-004 (Separate Marketing Domain Model), ADR-005 (Public Projection Pattern).
- **Critical rule (ADR-004):** This entity is **completely unrelated** to the operational `App\Models\Vessel`. No foreign key, no shared column, no code dependency connects them.

| Field Group | Classification | Description |
|------------|---------------|-------------|
| Identity | Internal | Surrogate primary key. |
| Public reference | Public | public_ref_code — stable public handle (e.g., "JSL-001"), used in URLs and inquiry references. Unique. |
| General info — type | Public | vessel_type (enum: Bulk Carrier, Tugboat, Barge, Other). |
| General info — year | Public | year_built (integer, nullable). |
| General info — flag | Public | flag_registry (string, nullable) — general registry, not owner-identifying. |
| General info — specs | Public | gross_tonnage, deadweight, loa_length, beam, draft, engine_power, trading_area (all nullable). |
| General info — description | Public | marketing_description (rich text). |
| General info — description (EN) | Public (nullable) | marketing_description_en — nullable (AR-24). |
| Sensitive — identity | **Sensitive** | real_vessel_name (string, nullable). **NEVER public.** |
| Sensitive — registration | **Sensitive** | imo_number (string, nullable). **NEVER public.** |
| Sensitive — ownership | **Sensitive** | owner_details (text, nullable). **NEVER public.** |
| Sensitive — commercial | **Confidential** | price_commercial_terms (text, nullable). **NEVER public.** (PRD §15.2 [RECOMMENDATION] to treat as sensitive.) |

> **Superseded (CR-001-001):** The original single `certificates` (text, nullable) column is **removed** and replaced by the dedicated `VesselCertificate` child entity (§5.2.3), which supports multiple structured certificate records per listing. See §5.2.3.
| Status | Public | status (enum: Open, Closed). Default: Open. |
| Audit | Internal | created_at, updated_at, deleted_at (soft delete). |

> **Security note (ADR-005):** The sensitive and confidential fields are stored in the same table as the public fields. The Public Projection Pattern ensures that public code paths **never select** these columns. The separation is enforced at the application/data-access layer (projection), not at the table level. See §15 for full security classification.

#### 5.2.2 VesselImage
- **Purpose:** Represents one image associated with a vessel listing. Child entity of VesselListing.
- **Ownership:** Marketing Domain.
- **Lifecycle:** Created → Reordered → Soft-deleted. Lifecycle bound to parent VesselListing.
- **Type classification:** Child entity (composition) of VesselListing aggregate root.
- **PRD reference:** FR-08, §15.4.
- **ARD reference:** ARD §8.2 Marketing Domain, ARD §11.3.

| Field Group | Description |
|------------|-------------|
| Identity | Surrogate primary key. |
| Parent | vessel_listing_id FK to `jsl_vessel_listings`. |
| Media | media_asset_id FK to `jsl_media_assets`. |
| Display | sort_order (integer), alt_text (string — must NOT contain sensitive data per ADR-005). |
| Audit | created_at, updated_at, deleted_at (soft delete). |

#### 5.2.3 VesselCertificate — *Added via [CR-001-001](../02-product/CR-001-001-Internal-Vessel-Certificate-Management.md)*
- **Purpose:** Represents one internal certificate record associated with a vessel listing (e.g., Certificate of Registry, Classification Certificate, Safety Management Certificate, IOPP Certificate, Other). Child entity of VesselListing. Supersedes the single free-text `certificates` column on `jsl_vessel_listings`.
- **Ownership:** Marketing Domain.
- **Lifecycle:** Created → Updated → Soft-deleted. See §8.7. (Expiry is a computed/informational state derived from `expiry_date`, not a stored lifecycle state — same convention as the thumbnail-by-`sort_order` rule for VesselImage.)
- **Type classification:** Child entity (composition) of VesselListing aggregate root — same composition pattern as VesselImage.
- **PRD reference:** FR-12, §15.2 (Sensitive Fields), per CR-001-001.
- **ARD reference:** ARD §8.2 Marketing Domain, ADR-004 (Separate Marketing Domain Model), ADR-005 (Public Projection Pattern) — both apply unmodified; no new ADR required.
- **Critical rule (ADR-005, inherited):** Every column on this entity is **Sensitive**. The Public Projection Pattern excludes this entity from public-facing code paths entirely — it is never joined, selected, or eager-loaded in any public query.

| Field Group | Classification | Description |
|------------|---------------|--------------|
| Identity | Internal | Surrogate primary key. |
| Parent | Internal | vessel_listing_id FK to `jsl_vessel_listings` (NOT NULL). |
| Certificate identity | **Sensitive** | certificate_type (string — e.g., 'certificate_of_registry', 'classification_certificate', 'safety_management_certificate', 'iopp_certificate', 'other'; CMS-curated select list, not a hard DB enum — see §10.5 rationale). |
| Certificate identity | **Sensitive** | certificate_number (string, nullable). |
| Certificate identity | **Sensitive** | issuing_authority (string, nullable). |
| Validity | **Sensitive** | issue_date (date, nullable), expiry_date (date, nullable). |
| Document | **Sensitive** | media_asset_id FK to `jsl_media_assets` (nullable — certificate may be recorded before the scanned document is uploaded). File **must** be stored on the `private` disk only (§14.7). |
| Notes | **Sensitive** | notes (text, nullable — internal remarks). |
| Audit | Internal | created_at, updated_at, deleted_at (soft delete). |

> **Security note:** Unlike `jsl_vessel_listings` (which mixes Public and Sensitive columns in one table, isolated by projection per ADR-005), `jsl_vessel_certificates` is a **wholly sensitive entity** — there is no public projection of this table at all. This is a stronger, simpler guarantee than column-level projection: the entity is structurally absent from public code paths.

### 5.3 Inquiry Domain Entities

#### 5.3.1 Inquiry
- **Purpose:** Stores a form-based inquiry submitted by a public visitor. WhatsApp and Email inquiries are client-side links and do **not** create records (PRD §16.3, AR-15).
- **Ownership:** Inquiry Domain.
- **Lifecycle:** New (default) → Read → Contacted → Archived → Soft-deleted (after ≥ 12-month retention). See §8.
- **Type classification:** Aggregate root.
- **PRD reference:** FR-04, FR-09, §16 (Inquiry Requirements).
- **ARD reference:** ARD §8.2 Inquiry Domain, ARD §9.3 Inquiry Flow.

| Field Group | Description |
|------------|-------------|
| Identity | Surrogate primary key. |
| Submitter info | name (required), company (nullable), email (nullable), phone (nullable). At least one of email/phone is required (application-level validation). |
| Message | message (text, required). |
| Association | vessel_listing_id FK to `jsl_vessel_listings` (nullable — NULL for general contact inquiries per FR-05). |
| Privacy | consent_given (boolean — consent checkbox per PRD NFR). |
| Status | status (enum: New, Read, Contacted, Archived). Default: New. |
| Audit | created_at, updated_at. |
| Retention | deleted_at (soft delete — applied after ≥ 12-month retention per PRD §16.4). |

> **Personal data note:** Inquiry records contain personal data (name, email, phone). These are classified as **Internal/Confidential** — visible only to CMS admin/broker, never public. Not shared with third parties (PRD NFR).

### 5.4 Media Domain Entities

#### 5.4.1 MediaAsset
- **Purpose:** Stores metadata for a media file (image) uploaded via the CMS. Referenced by VesselImage, GalleryItem, and Service (icon). The actual file is stored on disk (local `public` or non-public disk per ADR-009); this table stores the **metadata and path references**.
- **Ownership:** Media Domain.
- **Lifecycle:** Created → (orphaned if referencing entities are deleted) → Soft-deleted.
- **Type classification:** Aggregate root (reference entity).
- **PRD reference:** §14.5, §15.4.
- **ARD reference:** ARD §8.2 Media Domain, ARD §11 Media Architecture, ADR-009.

| Field Group | Description |
|------------|-------------|
| Identity | Surrogate primary key. |
| Storage | disk (string — 'public' or 'private'), file_path (string — obfuscated path), file_name (string — obfuscated filename). |
| File metadata | mime_type (string), size_bytes (integer), width (integer, nullable), height (integer, nullable). |
| Responsive variants | variant_thumbnail_path (string, nullable), variant_medium_path (string, nullable), variant_large_path (string, nullable). |
| Audit | created_at, updated_at, deleted_at (soft delete). |

> **EXIF note (ADR-005, ADR-009):** EXIF/metadata is stripped from the image file **before** storage. The `jsl_media_assets` table does not store EXIF data. The media pipeline (upload → EXIF strip → resize → store) is an application-layer concern; the database stores only the resulting file paths and metadata.

### 5.5 Value Objects

Formal value objects (immutable, no identity) are minimal in this MVP. The following are embedded within entities as columns rather than modeled as separate tables:

| Candidate Value Object | Embedded In | As Columns | Rationale |
|----------------------|------------|------------|-----------|
| Dimensions (LOA, beam, draft) | VesselListing | loa_length, beam, draft | Simple nullable decimals; no complex behavior; a separate table would be over-normalization. |
| Tonnage (GT, DWT) | VesselListing | gross_tonnage, deadweight | Same as above. |
| Contact info (email, phone) | Inquiry | email, phone | Simple strings; no shared identity. |
| Social links | SiteSettings | social_facebook_url, etc. | Simple URL strings; no complex behavior. |

No separate value-object tables are needed for the MVP.

---

## 6. Aggregate Design

**Reference: ARD §7 (Module Architecture), ARD §8 (Domain Architecture)**

### 6.1 Aggregate Root Identification

| Aggregate Root | Child Entity(ies) | Reference Entity(ies) | Domain | Rationale |
|---------------|-------------------|----------------------|--------|-----------|
| **VesselListing** | VesselImage, VesselCertificate | MediaAsset | Marketing | VesselListing owns its images and certificates (composition). Neither has meaning without a listing. All image/certificate operations go through the listing. MediaAsset is a reference (aggregation) — it exists independently and is shared. *(VesselCertificate added via CR-001-001.)* |
| **CompanyProfile** | (none) | (none) | Content | Singleton. No children. All fields are self-contained. |
| **Service** | (none) | MediaAsset (icon) | Content | Independent entity. MediaAsset is a reference (aggregation) for the optional icon. |
| **GalleryItem** | (none) | MediaAsset | Content | Independent entity. MediaAsset is a reference (aggregation) for the image. |
| **SiteSettings** | (none) | (none) | Content | Singleton. No children. All fields are self-contained. |
| **Inquiry** | (none) | VesselListing (optional) | Inquiry | Independent record. VesselListing is a reference (aggregation) — the listing exists independently of the inquiry. |
| **MediaAsset** | (none) | (none) | Media | Independent file metadata record. Referenced by other entities but owns itself. |

### 6.2 Aggregate Boundary Rules

| Rule | Enforcement |
|------|-------------|
| VesselImage cannot exist without a VesselListing. | Foreign key `vessel_listing_id` NOT NULL. Cascade soft-delete when listing is soft-deleted. |
| VesselImage operations go through VesselListing. | Application-layer rule: no direct VesselImage repository access from controllers; always via VesselListing aggregate. |
| VesselCertificate cannot exist without a VesselListing. *(CR-001-001)* | Foreign key `vessel_listing_id` NOT NULL. Cascade soft-delete when listing is soft-deleted. |
| VesselCertificate operations go through VesselListing. *(CR-001-001)* | Application-layer rule: no direct VesselCertificate repository access from controllers; always via VesselListing aggregate. CMS-only — never reachable from any public controller. |
| MediaAsset is shared and independent. | No cascade delete from referencing entities. MediaAsset has its own soft-delete. Orphaned media cleanup is an application-layer scheduled task. |
| Inquiry references VesselListing but is independent. | `vessel_listing_id` is nullable (NULL for general contact). No cascade delete — if a listing is deleted, the inquiry record retains its data with the FK set to NULL (or kept as-is for audit). |
| CompanyProfile and SiteSettings are singletons. | Application-layer enforcement: only one row allowed. DB constraint optional (id = 1 convention). |

### 6.3 Aggregate Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│  MARKETING DOMAIN                                                │
│                                                                  │
│  ┌──────────────────────────┐                                    │
│  │  VesselListing (Root)     │──── owns ────┐                    │
│  │                           │              │                    │
│  │                           │──── owns (CR-001-001) ──┐         │
│  └─────────────┬─────────────┘              │          │         │
│                │                            ▼          ▼         │
│                │                   ┌──────────────┐ ┌────────────────────┐
│                │                   │  VesselImage │ │  VesselCertificate │
│                │                   │  (Child)     │ │  (Child, Sensitive)│
│                │                   └──────┬───────┘ └─────────┬──────────┘
│                │                          │ refs               │ refs (nullable)
│                │                          ▼                    ▼
│                │                   ┌──────────────────────────────┐
│                │                   │  MediaAsset (Reference)       │
│                │                   │  shared by VesselImage and    │
│                │                   │  VesselCertificate documents  │
│                │                   └────────────────────────────────┘
│                │ refs (nullable)                                  │
└────────────────┼──────────────────────────────────────────────────┘
                 │
┌────────────────┼───────────────────────────────────────────────────┐
│  INQUIRY DOMAIN │                                                   │
│                 │                                                   │
│  ┌──────────────┴───────────┐                                       │
│  │  Inquiry (Root)           │ refs (nullable) to VesselListing     │
│  └───────────────────────────┘                                       │
└───────────────────────────────────────────────────────────────────┘
                 │
┌────────────────┼─────────────────────────────────────────────────┐
│  CONTENT DOMAIN │                                                 │
│                 │                                                 │
│  ┌──────────────────────────┐    refs    ┌──────────────────┐    │
│  │  Service (Root)           │───────────►│  MediaAsset      │    │
│  └──────────────────────────┘            │  (Reference)     │    │
│                                          └──────────────────┘    │
│  ┌──────────────────────────┐    refs    ┌──────────────────┐    │
│  │  GalleryItem (Root)       │───────────►│  MediaAsset      │    │
│  └──────────────────────────┘            │  (Reference)     │    │
│                                          └──────────────────┘    │
│  ┌──────────────────────────┐                                    │
│  │  CompanyProfile (Root)    │  (singleton, no refs)             │
│  └──────────────────────────┘                                    │
│  ┌──────────────────────────┐                                    │
│  │  SiteSettings (Root)      │  (singleton, no refs)             │
│  └──────────────────────────┘                                    │
└───────────────────────────────────────────────────────────────────┘

┌───────────────────────────────────────────────────────────────────┐
│  MEDIA DOMAIN                                                      │
│                                                                   │
│  ┌──────────────────┐                                             │
│  │  MediaAsset       │  (Reference entity, shared across domains) │
│  │  (Root)           │                                             │
│  └──────────────────┘                                             │
└───────────────────────────────────────────────────────────────────┘
```

---

## 7. Relationship Design

### 7.1 Relationship Summary

| Relationship | From | To | Type | Cardinality | Composition/Aggregation | FK Location | Nullable? |
|-------------|------|----|------|-------------|------------------------|-------------|-----------|
| R-1 | VesselListing | VesselImage | One-to-Many | 1 listing : 0..6 images | Composition | VesselImage.vessel_listing_id | NOT NULL |
| R-2 | VesselImage | MediaAsset | Many-to-One | N images : 1 media asset | Aggregation | VesselImage.media_asset_id | NOT NULL |
| R-3 | Service | MediaAsset | Many-to-One | N services : 0..1 media asset | Aggregation | Service.media_asset_id | Nullable |
| R-4 | GalleryItem | MediaAsset | Many-to-One | N gallery items : 1 media asset | Aggregation | GalleryItem.media_asset_id | NOT NULL |
| R-5 | Inquiry | VesselListing | Many-to-One | N inquiries : 0..1 listing | Aggregation | Inquiry.vessel_listing_id | Nullable |
| R-6 | CompanyProfile | (none) | — | — | — | — | — |
| R-7 | SiteSettings | (none) | — | — | — | — | — |
| R-8 *(CR-001-001)* | VesselListing | VesselCertificate | One-to-Many | 1 listing : 0..N certificates | Composition | VesselCertificate.vessel_listing_id | NOT NULL |
| R-9 *(CR-001-001)* | VesselCertificate | MediaAsset | Many-to-One | N certificates : 0..1 media asset | Aggregation | VesselCertificate.media_asset_id | Nullable |

### 7.2 Relationship Descriptions

#### R-1: VesselListing → VesselImage (One-to-Many, Composition)
- **Type:** Composition — VesselImage is a child entity that cannot exist without its parent VesselListing.
- **Cardinality:** One VesselListing has zero to six VesselImages. Maximum 6 enforced at application layer (see §9, §14).
- **FK:** `jsl_vessel_images.vessel_listing_id` → `jsl_vessel_listings.id` (NOT NULL).
- **Cascade behavior:** When a VesselListing is soft-deleted, its VesselImages are also soft-deleted (application-layer cascade, not DB-level cascade — to preserve audit trail).
- **Orphan prevention:** A VesselImage cannot be created without a valid VesselListing. FK is NOT NULL.

#### R-2: VesselImage → MediaAsset (Many-to-One, Aggregation)
- **Type:** Aggregation — MediaAsset exists independently and is shared (referenced by multiple entity types).
- **Cardinality:** Many VesselImages can reference one MediaAsset (though in practice, each image is unique per listing). One MediaAsset is referenced by exactly one VesselImage (in the vessel listing context).
- **FK:** `jsl_vessel_images.media_asset_id` → `jsl_media_assets.id` (NOT NULL).
- **Cascade behavior:** No cascade. Deleting a VesselImage soft-deletes the VesselImage row but does not delete the MediaAsset (it may be referenced elsewhere or needed for audit). Orphaned media cleanup is a scheduled task.

#### R-3: Service → MediaAsset (Many-to-One, Aggregation, Optional)
- **Type:** Aggregation — MediaAsset exists independently.
- **Cardinality:** Each Service has zero or one icon MediaAsset. One MediaAsset can be referenced by at most one Service icon.
- **FK:** `jsl_services.media_asset_id` → `jsl_media_assets.id` (Nullable).
- **Cascade behavior:** No cascade. If a Service is deleted, its icon MediaAsset remains.

#### R-4: GalleryItem → MediaAsset (Many-to-One, Aggregation)
- **Type:** Aggregation — MediaAsset exists independently.
- **Cardinality:** Each GalleryItem has exactly one MediaAsset. One MediaAsset is referenced by exactly one GalleryItem (in the gallery context).
- **FK:** `jsl_gallery_items.media_asset_id` → `jsl_media_assets.id` (NOT NULL).
- **Cascade behavior:** No cascade. If a GalleryItem is deleted, its MediaAsset remains (orphaned media cleanup is scheduled).

#### R-5: Inquiry → VesselListing (Many-to-One, Aggregation, Optional)
- **Type:** Aggregation — VesselListing exists independently of Inquiry.
- **Cardinality:** Many Inquiries can reference one VesselListing (multiple inquiries about the same vessel). An Inquiry may also have NULL vessel_listing_id (general contact inquiry, per FR-05).
- **FK:** `jsl_inquiries.vessel_listing_id` → `jsl_vessel_listings.id` (Nullable).
- **Cascade behavior:** If a VesselListing is soft-deleted, inquiries referencing it are **not** deleted — they retain their data for audit. The FK remains (soft-deleted listings still exist in the database with `deleted_at` set). If a listing is hard-deleted (force-delete), the FK should be set to NULL on related inquiries to preserve the inquiry record (application-layer behavior).

#### R-8: VesselListing → VesselCertificate (One-to-Many, Composition) — *Added via CR-001-001*
- **Type:** Composition — VesselCertificate is a child entity that cannot exist without its parent VesselListing. Same pattern as R-1.
- **Cardinality:** One VesselListing has zero to many VesselCertificates (no upper limit; unlike images, certificates are not capped at 6).
- **FK:** `jsl_vessel_certificates.vessel_listing_id` → `jsl_vessel_listings.id` (NOT NULL).
- **Cascade behavior:** When a VesselListing is soft-deleted, its VesselCertificates are also soft-deleted (application-layer cascade, same as R-1).
- **Orphan prevention:** A VesselCertificate cannot be created without a valid VesselListing. FK is NOT NULL.

#### R-9: VesselCertificate → MediaAsset (Many-to-One, Aggregation, Optional) — *Added via CR-001-001*
- **Type:** Aggregation — MediaAsset exists independently and is shared.
- **Cardinality:** Each VesselCertificate has zero or one document MediaAsset (a certificate may be recorded before its scanned document is uploaded). One MediaAsset is referenced by exactly one VesselCertificate (in the certificate context).
- **FK:** `jsl_vessel_certificates.media_asset_id` → `jsl_media_assets.id` (Nullable).
- **Cascade behavior:** No cascade. The referenced MediaAsset **must** be stored on the `private` disk (§14.7) — certificates are Sensitive and never public, unlike VesselImage's `public` disk.

### 7.3 No Many-to-Many Relationships

There are no many-to-many relationships in the MVP data model. All relationships are one-to-many or many-to-one. No pivot/junction tables are needed.

### 7.4 No One-to-One Relationships (beyond singletons)

There are no one-to-one relationships between separate tables. CompanyProfile and SiteSettings are singletons (single-row tables), not one-to-one relationships to other entities.

---

## 8. Entity Lifecycle

**Reference: PRD §15.3 (Listing Lifecycle), PRD §13 (Inquiry status), ARD §8.2**

### 8.1 VesselListing Lifecycle

```
                    ┌──────────┐
                    │  Created  │
                    └─────┬────┘
                          │ default status
                          ▼
                    ┌──────────┐
        ┌──────────►│   Open   │◄──────────┐
        │           └─────┬────┘           │
        │                 │ admin toggles  │
        │                 ▼                │
        │           ┌──────────┐           │
        │           │  Closed  │───────────┘
        │           └─────┬────┘
        │                 │ admin reopens
        │                 │ (or closes again)
        │                 │
        │                 │ admin soft-deletes
        │                 ▼
        │           ┌──────────┐
        │           │  Deleted  │ (soft-delete: deleted_at set)
        │           │ (hidden)  │
        │           └─────┬────┘
        │                 │ admin restores
        └─────────────────┘
                          │
                          │ admin force-deletes (permanent)
                          ▼
                    ┌──────────┐
                    │  Removed  │ (permanently removed from DB)
                    └──────────┘
```

**State descriptions:**

| State | Public visibility | CMS visibility | Inquiries accepted | deleted_at | Notes |
|-------|------------------|----------------|-------------------|------------|-------|
| Open | ✅ Visible | ✅ Visible | ✅ Yes | NULL | Default state on creation (PRD §15.3). |
| Closed | ✅ Visible (marked closed) | ✅ Visible | ❌ No | NULL | Per PRD §15.3 [RECOMMENDATION]: Closed listings remain visible but inquiries disabled. |
| Deleted (soft) | ❌ Hidden | ✅ Visible (in trashed filter) | ❌ No | Set | Recoverable. Admin can restore. |
| Removed (force) | ❌ Gone | ❌ Gone | ❌ No | — | Permanent deletion. VesselImages also permanently removed. |

**Allowed transitions:**

| From | To | Trigger |
|------|----|---------|
| Created | Open | Default on creation (PRD §15.3: defaults to Open). |
| Created | Closed | Admin chooses Closed on creation. |
| Open | Closed | Admin toggles status. |
| Closed | Open | Admin toggles status. |
| Open | Deleted (soft) | Admin soft-deletes. |
| Closed | Deleted (soft) | Admin soft-deletes. |
| Deleted (soft) | Open or Closed | Admin restores (returns to previous status). |
| Deleted (soft) | Removed | Admin force-deletes. |

### 8.2 Inquiry Lifecycle

```
┌──────────┐
│   New    │ (default on form submission)
└─────┬────┘
      │ admin views
      ▼
┌──────────┐
│   Read   │
└─────┬────┘
      │ admin contacts submitter
      ▼
┌──────────┐
│ Contacted │
└─────┬────┘
      │ admin archives
      ▼
┌──────────┐
│ Archived  │
└─────┬────┘
      │ after ≥ 12-month retention
      ▼
┌──────────┐
│  Soft-    │ (deleted_at set; retained for audit)
│  Deleted  │
└──────────┘
```

**State descriptions:**

| State | CMS visibility | Meaning | deleted_at |
|-------|----------------|---------|------------|
| New | ✅ Visible (bold/unread indicator) | Freshly submitted, not yet viewed. | NULL |
| Read | ✅ Visible | Admin has opened/viewed the inquiry. | NULL |
| Contacted | ✅ Visible | Admin has contacted the submitter. | NULL |
| Archived | ✅ Visible (in archived filter) | Inquiry is closed/resolved/no longer active. | NULL |
| Soft-Deleted | ✅ Visible (in trashed filter) | Retained for audit per PRD §16.4 (≥ 12 months). | Set |

> **Note:** The New → Read → Contacted → Archived progression is a [RECOMMENDATION]-level feature (PRD FR-09 [RECOMMENDATION]). The [CONFIRMED] requirement is that admin can view inquiries. The status enum is designed to support the recommended workflow but the minimum MVP can function with just "New" and "Archived" if status flags are simplified.

### 8.3 Service Lifecycle

| State | Public visibility | is_visible | deleted_at |
|-------|------------------|------------|------------|
| Visible | ✅ Visible on public site | TRUE | NULL |
| Hidden | ❌ Hidden from public site | FALSE | NULL |
| Soft-Deleted | ❌ Hidden | (any) | Set |

**Transitions:** Visible ↔ Hidden (toggle `is_visible`). Any state → Soft-Deleted.

### 8.4 GalleryItem Lifecycle

| State | Public visibility | deleted_at |
|-------|------------------|------------|
| Active | ✅ Visible | NULL |
| Soft-Deleted | ❌ Hidden | Set |

**Transitions:** Active → Soft-Deleted → Active (restored). Simple two-state lifecycle.

### 8.5 MediaAsset Lifecycle

| State | Referenced? | deleted_at |
|-------|------------|------------|
| Active | Yes (by at least one entity) | NULL |
| Orphaned | No (referencing entities deleted) | NULL |
| Soft-Deleted | No | Set |

**Transitions:** Active → Orphaned (when all referencing entities are deleted). Orphaned → Soft-Deleted (by scheduled cleanup task). Soft-Deleted → permanently removed (by scheduled purge).

### 8.6 CompanyProfile and SiteSettings Lifecycle

| State | Description |
|-------|-------------|
| Active | The single row exists. Updated as needed. No deletion. |

No state transitions — these are singleton records that are created once and updated.

### 8.7 VesselCertificate Lifecycle — *Added via CR-001-001*

| State | CMS visibility | Public visibility | deleted_at |
|-------|----------------|-------------------|------------|
| Active | ✅ Visible | ❌ Never | NULL |
| Soft-Deleted | ✅ Visible (in trashed filter) | ❌ Never | Set |

**Transitions:** Active → Soft-Deleted → Active (restored). Simple two-state lifecycle, bound to the parent VesselListing (same shape as GalleryItem, §8.4).

> **Expiry is not a lifecycle state.** Whether a certificate is "expiring soon" or "expired" is computed at read time by comparing `expiry_date` to the current date — it is not a stored status column. The CMS surfaces this as a visual indicator (PRD FR-12 [RECOMMENDATION]), not a state transition.

---

## 9. Constraints

### 9.1 Business Constraints

| # | Constraint | Source | Enforcement Layer |
|---|-----------|--------|-------------------|
| BC-1 | Maximum 6 images per vessel listing. | PRD §15.4, FR-08 | Application layer (count existing images before upload; reject if ≥ 6). Optionally a row-level check constraint or trigger (DB-dependent). |
| BC-2 | Vessel listing default status is Open. | PRD §15.3 | Application layer (default value on creation). DB default constraint. |
| BC-3 | Sensitive fields (real_vessel_name, imo_number, owner_details, price_commercial_terms on `jsl_vessel_listings`; all columns on `jsl_vessel_certificates`) must NEVER appear in public output. | PRD AC-4, ADR-005 | Application layer (Public Projection Pattern). DB does not enforce public/internal distinction — it stores all fields; the projection layer controls what is selected. For `jsl_vessel_certificates`, the entity is structurally excluded from public queries entirely (never joined). |
| BC-11 *(CR-001-001)* | A VesselCertificate's document MediaAsset must be stored on the `private` disk only — never `public`. | PRD §15.2, ADR-005, ADR-009 | Application layer (media pipeline enforces disk choice based on owning entity type at upload time). |
| BC-12 *(CR-001-001)* | A vessel listing may have any number of certificates (no maximum cap, unlike the 6-image limit on VesselImage). | PRD FR-12 | Application layer (no count restriction). |
| BC-4 | Inquiry requires at least one of email or phone. | PRD §16.1 | Application layer (form validation). DB allows both nullable; application-level rule enforces "at least one." |
| BC-5 | Inquiry consent checkbox must be checked for form submission. | PRD NFR | Application layer (form validation). DB stores `consent_given` as boolean. |
| BC-6 | Image alt text must NOT contain sensitive data (vessel name, IMO, owner). | ADR-005, ARD §10.4 | Application layer (admin guideline + CMS validation hint). Not a DB constraint — human/editorial control. |
| BC-7 | EXIF/metadata stripped from all uploaded images before storage. | ADR-005, ADR-009 | Application layer (media pipeline). DB does not store EXIF. |
| BC-8 | No foreign keys between `jsl_` tables and operational tables. | ADR-006, ARD §18 C-10 | Schema design (no FK definitions cross the boundary). CI static analysis. |
| BC-9 | Inquiry records retained ≥ 12 months before soft-deletion. | PRD §16.4 | Application layer (scheduled task soft-deletes inquiries older than 12 months in Archived state). |
| BC-10 | CompanyProfile and SiteSettings are singletons (one row each). | AR-22, §5 | Application layer (enforce single row). DB convention (id = 1). |

### 9.2 Database Constraints

| # | Constraint | Table | Column(s) | Type |
|---|-----------|-------|-----------|------|
| DC-1 | Primary key on every table. | All tables | `id` | PRIMARY KEY |
| DC-2 | public_ref_code is unique. | `jsl_vessel_listings` | `public_ref_code` | UNIQUE |
| DC-3 | vessel_listing_id must reference an existing listing. | `jsl_vessel_images` | `vessel_listing_id` | FOREIGN KEY (NOT NULL) |
| DC-4 | media_asset_id must reference an existing asset. | `jsl_vessel_images`, `jsl_gallery_items` | `media_asset_id` | FOREIGN KEY (NOT NULL) |
| DC-5 | media_asset_id (icon) may be NULL. | `jsl_services` | `media_asset_id` | FOREIGN KEY (Nullable) |
| DC-6 | vessel_listing_id may be NULL (general contact). | `jsl_inquiries` | `vessel_listing_id` | FOREIGN KEY (Nullable) |
| DC-7 | status values restricted to enum. | `jsl_vessel_listings` | `status` | CHECK / ENUM (values: 'open', 'closed') |
| DC-8 | inquiry status values restricted to enum. | `jsl_inquiries` | `status` | CHECK / ENUM (values: 'new', 'read', 'contacted', 'archived') |
| DC-9 | is_visible is boolean. | `jsl_services` | `is_visible` | BOOLEAN / TINYINT(1) |
| DC-10 | consent_given is boolean. | `jsl_inquiries` | `consent_given` | BOOLEAN / TINYINT(1) |
| DC-11 | sort_order is integer. | `jsl_vessel_images`, `jsl_services`, `jsl_gallery_items` | `sort_order` | INTEGER |
| DC-12 | deleted_at is nullable timestamp. | All soft-deletable tables | `deleted_at` | TIMESTAMP (Nullable) |
| DC-13 | created_at, updated_at are timestamps. | All tables | `created_at`, `updated_at` | TIMESTAMP |
| DC-14 *(CR-001-001)* | vessel_listing_id must reference an existing listing. | `jsl_vessel_certificates` | `vessel_listing_id` | FOREIGN KEY (NOT NULL) |
| DC-15 *(CR-001-001)* | media_asset_id (certificate document) may be NULL. | `jsl_vessel_certificates` | `media_asset_id` | FOREIGN KEY (Nullable) |

### 9.3 Validation Constraints (Application Layer)

| # | Constraint | Entity | Field(s) | Rule |
|---|-----------|--------|----------|------|
| VC-1 | Name required. | Inquiry | name | Not empty, max 255 chars. |
| VC-2 | Message required. | Inquiry | message | Not empty. |
| VC-3 | At least one of email or phone. | Inquiry | email, phone | `email != null OR phone != null`. |
| VC-4 | Email format (if provided). | Inquiry | email | Valid email format. |
| VC-5 | public_ref_code format. | VesselListing | public_ref_code | Alphanumeric with hyphens, e.g., "JSL-001". Unique. |
| VC-6 | year_built range. | VesselListing | year_built | Integer, 1900–current year, nullable. |
| VC-7 | Max image count. | VesselListing (images) | — | ≤ 6 images per listing. |
| VC-8 | Max upload size. | MediaAsset | size_bytes | ≤ configured max (e.g., 5MB). |
| VC-9 | Rich text sanitization. | CompanyProfile, Service, VesselListing | text fields | HTML allow-list sanitization on save and render (AR-14). |
| VC-10 *(CR-001-001)* | Expiry after issue. | VesselCertificate | issue_date, expiry_date | If both provided, `expiry_date >= issue_date` (application-level validation; both fields independently nullable). |

### 9.4 Uniqueness

| Table | Unique Column(s) | Scope |
|-------|-----------------|-------|
| `jsl_vessel_listings` | `public_ref_code` | Global (all listings). |
| `jsl_company_profiles` | `id` (= 1) | Singleton. |
| `jsl_site_settings` | `id` (= 1) | Singleton. |

No composite unique constraints are needed in the MVP.

### 9.5 Status Transition Rules

| Entity | Allowed Transitions | Disallowed Transitions |
|--------|--------------------|-----------------------|
| VesselListing | Open ↔ Closed; any → Deleted; Deleted → restored. | Direct from Deleted to Removed without admin action. |
| Inquiry | New → Read → Contacted → Archived; any → Deleted. | Backward transitions (Archived → New) allowed but not recommended. |
| Service | Visible ↔ Hidden; any → Deleted. | — |

---

## 10. Naming Convention

### 10.1 Table Naming

| Rule | Example | Source |
|------|---------|--------|
| All JSL website tables use the `jsl_` prefix. | `jsl_vessel_listings`, `jsl_inquiries` | ADR-006, ARD §18 R-5 |
| Table names are snake_case, plural. | `jsl_vessel_listings` (not `jsl_vesselListing` or `jsl_vessellisting`) | Laravel convention |
| Pivot/junction tables (if any in future): `jsl_<entity_a>_<entity_b>` alphabetical. | (none in MVP) | Laravel convention |
| No operational table uses the `jsl_` prefix. | (existing `vessels`, `shipments`, etc. remain unchanged) | ADR-006 |

### 10.2 Column Naming

| Rule | Example | Source |
|------|---------|--------|
| Columns are snake_case. | `public_ref_code`, `vessel_type`, `created_at` | Laravel convention |
| Foreign key columns: `<singular_entity>_id`. | `vessel_listing_id`, `media_asset_id` | Laravel convention |
| Boolean columns: `is_<adjective>` or `has_<noun>` or `<verb>_given`. | `is_visible`, `consent_given` | Convention |
| Timestamp columns: `<event>_at`. | `created_at`, `updated_at`, `deleted_at` | Laravel convention |
| English (EN) translation columns: `<field>_en`. | `title_en`, `description_en`, `about_en` | AR-24 |
| Sensitive field columns: descriptively named, no obfuscation. | `real_vessel_name`, `imo_number`, `owner_details` | Clarity for CMS admins |

### 10.3 Foreign Key Naming

| Rule | Example |
|------|---------|
| FK columns: `<singular_entity>_id`. | `vessel_listing_id` |
| FK constraints: `<table>_<column>_foreign` (Laravel auto-naming). | `jsl_vessel_images_vessel_listing_id_foreign` |

### 10.4 Index Naming

| Rule | Example |
|------|---------|
| Single-column index: `<table>_<column>_index`. | `jsl_vessel_listings_status_index` |
| Composite index: `<table>_<column_a>_<column_b>_index`. | `jsl_vessel_images_vessel_listing_id_sort_order_index` |
| Unique index: `<table>_<column>_unique`. | `jsl_vessel_listings_public_ref_code_unique` |

### 10.5 Enum Naming

| Entity | Column | Values (stored as string) |
|--------|--------|--------------------------|
| VesselListing | vessel_type | `bulk_carrier`, `tugboat`, `barge`, `other` |
| VesselListing | status | `open`, `closed` |
| Inquiry | status | `new`, `read`, `contacted`, `archived` |

> **Note:** Enums are stored as strings (VARCHAR with CHECK constraint or ENUM type). The application layer (PHP enum) maps these to human-readable labels. Storing as string (not integer) preserves readability in direct DB queries and aligns with Laravel's enum handling conventions.

> **VesselCertificate.certificate_type exception *(CR-001-001)*:** Stored as a plain VARCHAR, **not** a DB-level ENUM/CHECK. Certificate types vary more than `vessel_type`/`status` and the business may need to add a type without a migration. The allow-list (`certificate_of_registry`, `classification_certificate`, `safety_management_certificate`, `iopp_certificate`, `other`, …) is curated and validated at the application layer (CMS select options), not enforced by the database. This is a deliberate flexibility trade-off, documented here per the normalization/denormalization rationale style used in §11.4.

### 10.6 Media Path Naming

| Element | Convention | Example |
|---------|-----------|---------|
| Storage directory | `jsl-media/<entity-type>/` | `jsl-media/vessel-listings/` |
| Filename | Obfuscated (UUID or random string) | `a3f7b2c1d4e5.jpg` |
| Variant suffix | `_<variant>` before extension | `a3f7b2c1d4e5_thumb.webp`, `a3f7b2c1d4e5_medium.webp`, `a3f7b2c1d4e5_large.webp` |

---

## 11. Normalization Review

### 11.1 First Normal Form (1NF)

| Check | Status | Notes |
|-------|--------|-------|
| All columns contain atomic values. | ✅ Pass | No repeating groups, no arrays in single columns. |
| Each row has a primary key. | ✅ Pass | All tables have surrogate `id` primary key. |
| No repeating groups. | ✅ Pass | Vessel images are in a separate child table (`jsl_vessel_images`), not comma-separated in the listing. |

### 11.2 Second Normal Form (2NF)

| Check | Status | Notes |
|-------|--------|-------|
| All non-key attributes depend on the entire primary key. | ✅ Pass | All tables use single-column surrogate primary keys (`id`), so partial dependency is impossible by construction. |

### 11.3 Third Normal Form (3NF)

| Check | Status | Notes |
|-------|--------|-------|
| No transitive dependencies (non-key attributes depend on nothing but the primary key). | ✅ Pass | All non-key columns describe only their owning entity. |

**Transitive dependency check detail:**

| Table | Potential concern | Analysis | Result |
|-------|------------------|----------|--------|
| `jsl_vessel_listings` | Are general specs transitively dependent? | All spec fields (gross_tonnage, deadweight, etc.) describe the vessel listing itself. No transitive dependency. | ✅ 3NF |
| `jsl_media_assets` | Are variant paths transitively dependent? | Variant paths describe the media asset itself. No transitive dependency. | ✅ 3NF |
| `jsl_inquiries` | Is submitter info transitively dependent? | Name, email, phone describe the inquiry (not a separate submitter entity). For MVP volume, a separate submitter table would be over-normalization. | ✅ 3NF |
| `jsl_vessel_certificates` *(CR-001-001)* | Are certificate fields transitively dependent? | Type, number, issuing authority, dates, and notes all describe the certificate record itself, scoped to its parent listing via `vessel_listing_id`. No transitive dependency. | ✅ 3NF |

### 11.4 Denormalization Decisions

| # | Decision | Rationale | Impact |
|---|---------|-----------|--------|
| DN-1 | **Sensitive fields remain in `jsl_vessel_listings`** (not split into a separate 1:1 table). | A separate `jsl_vessel_listing_sensitive_details` table would add a join for every CMS operation. The Public Projection Pattern (ADR-005) already enforces sensitive-field isolation at the application layer. Defense-in-depth does not require physical table separation for the MVP. | Simpler CMS CRUD; projection pattern handles public isolation. |
| DN-2 | **Responsive variant paths stored in `jsl_media_assets`** (not a separate `jsl_media_variants` table). | Three variant paths (thumbnail, medium, large) are fixed and always present together. A separate variants table would add a join for every image render. | Simpler queries; slight redundancy (3 nullable path columns). |
| DN-3 | **Inquiry submitter info embedded in `jsl_inquiries`** (not a separate `jsl_inquiry_submitters` table). | MVP has no concept of registered submitters or submitter profiles. Each inquiry is a one-time submission. A submitter table would be over-normalization. | Simpler queries; no submitter deduplication (acceptable for MVP). |
| DN-4 | **Site settings in a single wide table** (`jsl_site_settings`) rather than a key-value store. | The settings are a fixed, small set of fields. A key-value (EAV) approach would add complexity and lose type safety. | Simpler queries; type-safe columns; less flexible (acceptable — settings set is fixed for MVP). |

**Normalization verdict:** The schema is in **3NF**. Denormalization decisions are deliberate, documented, and driven by MVP simplicity. None violate ADR or ARD constraints.

---

## 12. Logical ERD

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        LOGICAL ERD — JSL WEBSITE MODULE                      │
│                        (all tables prefixed jsl_)                            │
│                                                                              │
│  ┌───────────────────────┐                                                  │
│  │  jsl_company_profiles  │  (singleton)                                    │
│  │  ───────────────────── │                                                  │
│  │  id (PK)               │                                                  │
│  │  about                 │                                                  │
│  │  overview              │                                                  │
│  │  vision                │                                                  │
│  │  mission               │                                                  │
│  │  about_en (nullable)   │                                                  │
│  │  overview_en (nullable)│                                                  │
│  │  vision_en (nullable)  │                                                  │
│  │  mission_en (nullable) │                                                  │
│  │  created_at            │                                                  │
│  │  updated_at            │                                                  │
│  └───────────────────────┘                                                  │
│                                                                              │
│  ┌───────────────────────┐                  ┌───────────────────────┐       │
│  │  jsl_site_settings     │  (singleton)    │  jsl_media_assets      │       │
│  │  ───────────────────── │                  │  ───────────────────── │       │
│  │  id (PK)               │                  │  id (PK)               │       │
│  │  site_name             │                  │  disk                  │       │
│  │  tagline (nullable)    │                  │  file_path             │       │
│  │  footer_text (nullable)│                  │  file_name             │       │
│  │  site_name_en (null)   │                  │  mime_type             │       │
│  │  tagline_en (null)     │                  │  size_bytes            │       │
│  │  footer_text_en (null) │                  │  width (nullable)      │       │
│  │  contact_address (null)│                  │  height (nullable)     │       │
│  │  contact_phone (null)  │                  │  variant_thumb (null)  │       │
│  │  contact_email (null)  │                  │  variant_medium (null) │       │
│  │  social_facebook (null)│                  │  variant_large (null)  │       │
│  │  social_instagram(null)│                  │  created_at            │       │
│  │  social_linkedin (null)│                  │  updated_at            │       │
│  │  created_at            │                  │  deleted_at (nullable) │       │
│  │  updated_at            │                  └───────────┬───────────┘       │
│  └───────────────────────┘                              │                     │
│                                                         │ referenced by       │
│                                                         │                     │
│  ┌───────────────────────┐                              │                     │
│  │  jsl_services          │                              │                     │
│  │  ───────────────────── │                              │                     │
│  │  id (PK)               │──── media_asset_id (FK,null)─┘                     │
│  │  title                 │                                                    │
│  │  description           │                                                    │
│  │  title_en (nullable)   │                                                    │
│  │  description_en (null) │                                                    │
│  │  is_visible            │                                                    │
│  │  sort_order            │                                                    │
│  │  created_at            │                                                    │
│  │  updated_at            │                                                    │
│  │  deleted_at (nullable) │                                                    │
│  └───────────────────────┘                                                    │
│                                                                                │
│  ┌───────────────────────┐                                                    │
│  │  jsl_gallery_items     │                                                    │
│  │  ───────────────────── │                                                    │
│  │  id (PK)               │──── media_asset_id (FK, NOT NULL)──────────────►  │
│  │  caption (nullable)    │                                                    │
│  │  category (nullable)   │                                                    │
│  │  caption_en (nullable) │                                                    │
│  │  sort_order            │                                                    │
│  │  created_at            │                                                    │
│  │  updated_at            │                                                    │
│  │  deleted_at (nullable) │                                                    │
│  └───────────────────────┘                                                    │
│                                                                                │
│  ┌───────────────────────────────┐                                            │
│  │  jsl_vessel_listings           │  (Aggregate Root)                         │
│  │  ───────────────────────────── │                                            │
│  │  id (PK)                       │                                            │
│  │  public_ref_code (UNIQUE)      │                                            │
│  │  vessel_type (enum)            │                                            │
│  │  year_built (nullable)         │                                            │
│  │  flag_registry (nullable)      │  ── PUBLIC FIELDS ──                      │
│  │  gross_tonnage (nullable)      │                                            │
│  │  deadweight (nullable)         │                                            │
│  │  loa_length (nullable)         │                                            │
│  │  beam (nullable)               │                                            │
│  │  draft (nullable)              │                                            │
│  │  engine_power (nullable)       │                                            │
│  │  trading_area (nullable)       │                                            │
│  │  marketing_description         │                                            │
│  │  marketing_description_en(null)│                                            │
│  │  ──────────────────────────────│                                            │
│  │  real_vessel_name (nullable)   │  ── SENSITIVE (NEVER PUBLIC) ──           │
│  │  imo_number (nullable)         │                                            │
│  │  owner_details (nullable)      │                                            │
│  │  price_commercial_terms (null) │  ── CONFIDENTIAL (NEVER PUBLIC) ──        │
│  │  ──────────────────────────────│                                            │
│  │  status (enum: open/closed)    │  ── PUBLIC ──                              │
│  │  created_at                    │                                            │
│  │  updated_at                    │                                            │
│  │  deleted_at (nullable)         │                                            │
│  └───────────────┬───────────────┘                                            │
│                  │ 1                                                            │
│                  │                                                              │
│                  │ 0..6                                                         │
│                  ▼                                                              │
│  ┌───────────────────────────────┐                                            │
│  │  jsl_vessel_images             │  (Child Entity)                           │
│  │  ───────────────────────────── │                                            │
│  │  id (PK)                       │                                            │
│  │  vessel_listing_id (FK,NOT NULL)── to jsl_vessel_listings.id               │
│  │  media_asset_id (FK, NOT NULL) ── to jsl_media_assets.id                   │
│  │  sort_order                    │                                            │
│  │  alt_text                      │  (must NOT contain sensitive data)        │
│  │  created_at                    │                                            │
│  │  updated_at                    │                                            │
│  │  deleted_at (nullable)         │                                            │
│  └───────────────────────────────┘                                            │
│                  ▲                                                            │
│                  │ 1                                                          │
│                  │ 0..N (no max)                                              │
│                  │                                                            │
│  ┌───────────────┴───────────────┐  jsl_vessel_certificates (CR-001-001)      │
│  │  jsl_vessel_certificates        │  (Child Entity — ALL FIELDS SENSITIVE)    │
│  │  ───────────────────────────── │                                            │
│  │  id (PK)                       │                                            │
│  │  vessel_listing_id (FK,NOT NULL)── to jsl_vessel_listings.id               │
│  │  certificate_type              │  ── SENSITIVE (NEVER PUBLIC) ──           │
│  │  certificate_number (nullable) │                                            │
│  │  issuing_authority (nullable)  │                                            │
│  │  issue_date (nullable)         │                                            │
│  │  expiry_date (nullable)        │                                            │
│  │  media_asset_id (FK, nullable) │── to jsl_media_assets.id (private disk)   │
│  │  notes (nullable)              │                                            │
│  │  created_at                    │                                            │
│  │  updated_at                    │                                            │
│  │  deleted_at (nullable)         │                                            │
│  └───────────────────────────────┘                                            │
│                                                                                │
│  ┌───────────────────────────────┐                                            │
│  │  jsl_inquiries                 │                                            │
│  │  ───────────────────────────── │                                            │
│  │  id (PK)                       │                                            │
│  │  name                          │                                            │
│  │  company (nullable)            │                                            │
│  │  email (nullable)              │                                            │
│  │  phone (nullable)              │                                            │
│  │  message                       │                                            │
│  │  vessel_listing_id (FK, null) ── to jsl_vessel_listings.id (nullable)      │
│  │  consent_given                 │                                            │
│  │  status (enum: new/read/etc)   │                                            │
│  │  created_at                    │                                            │
│  │  updated_at                    │                                            │
│  │  deleted_at (nullable)         │  (after ≥ 12-month retention)             │
│  └───────────────────────────────┘                                            │
│                                                                                │
│  ──────────────────────────────────────────────────────────────────────────  │
│  EXISTING TABLES (reused, not created by this DBD):                           │
│                                                                                │
│  ┌──────────────────┐     ┌──────────────────────────────┐                   │
│  │  users (existing) │     │  Spatie Permission tables     │                   │
│  │  (web guard)      │     │  - roles, permissions         │                   │
│  │                   │     │  - model_has_roles, etc.      │                   │
│  └──────────────────┘     └──────────────────────────────┘                   │
│                                                                                │
│  ┌──────────────────────────────┐                                              │
│  │  Spatie activity_log (existing)│  (audit log for CMS admin actions)        │
│  └──────────────────────────────┘                                              │
│                                                                                │
│  ──────────────────────────────────────────────────────────────────────────  │
│  OPERATIONAL TABLES (existing, NOT modified, NOT referenced by jsl_ tables):  │
│  vessels, shipments, voyages, branches, etc.                                  │
│  No FK from jsl_ tables to operational tables. No FK from operational to jsl. │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## 13. Physical Data Model

### 13.1 Table: `jsl_company_profiles`

| Column | Type | Null | Default | Key | Notes |
|--------|------|------|---------|-----|-------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | PK | Singleton (id = 1). |
| about | LONGTEXT | YES | NULL | — | Rich text (HTML). |
| overview | LONGTEXT | YES | NULL | — | Rich text (HTML). |
| vision | TEXT | YES | NULL | — | |
| mission | TEXT | YES | NULL | — | |
| about_en | LONGTEXT | YES | NULL | — | EN translation (AR-24). |
| overview_en | LONGTEXT | YES | NULL | — | EN translation. |
| vision_en | TEXT | YES | NULL | — | EN translation. |
| mission_en | TEXT | YES | NULL | — | EN translation. |
| created_at | TIMESTAMP | YES | NULL | — | |
| updated_at | TIMESTAMP | YES | NULL | — | |

- **PK:** `id`
- **FK:** none
- **Unique:** none (singleton by convention)
- **Indexes:** none needed (single row)
- **Soft Delete:** No (singleton, never deleted)

### 13.2 Table: `jsl_site_settings`

| Column | Type | Null | Default | Key | Notes |
|--------|------|------|---------|-----|-------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | PK | Singleton (id = 1). |
| site_name | VARCHAR(255) | NO | | — | |
| tagline | VARCHAR(255) | YES | NULL | — | |
| footer_text | TEXT | YES | NULL | — | |
| site_name_en | VARCHAR(255) | YES | NULL | — | EN (AR-24). |
| tagline_en | VARCHAR(255) | YES | NULL | — | EN. |
| footer_text_en | TEXT | YES | NULL | — | EN. |
| contact_address | TEXT | YES | NULL | — | Display only. |
| contact_phone_display | VARCHAR(50) | YES | NULL | — | Display only (routing number is in .env). |
| contact_email_display | VARCHAR(255) | YES | NULL | — | Display only (routing email is in .env). |
| social_facebook_url | VARCHAR(500) | YES | NULL | — | |
| social_instagram_url | VARCHAR(500) | YES | NULL | — | |
| social_linkedin_url | VARCHAR(500) | YES | NULL | — | |
| created_at | TIMESTAMP | YES | NULL | — | |
| updated_at | TIMESTAMP | YES | NULL | — | |

- **PK:** `id`
- **FK:** none
- **Unique:** none (singleton by convention)
- **Indexes:** none needed (single row)
- **Soft Delete:** No (singleton, never deleted)

### 13.3 Table: `jsl_services`

| Column | Type | Null | Default | Key | Notes |
|--------|------|------|---------|-----|-------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | PK | |
| title | VARCHAR(255) | NO | | — | |
| description | LONGTEXT | YES | NULL | — | Rich text (HTML). |
| title_en | VARCHAR(255) | YES | NULL | — | EN (AR-24). |
| description_en | LONGTEXT | YES | NULL | — | EN. |
| media_asset_id | BIGINT UNSIGNED | YES | NULL | FK | Optional icon/image. → `jsl_media_assets.id` |
| is_visible | BOOLEAN | NO | TRUE | — | Show/hide on public site. |
| sort_order | INT | NO | 0 | — | Display ordering. |
| created_at | TIMESTAMP | YES | NULL | — | |
| updated_at | TIMESTAMP | YES | NULL | — | |
| deleted_at | TIMESTAMP | YES | NULL | — | Soft delete. |

- **PK:** `id`
- **FK:** `media_asset_id` → `jsl_media_assets.id` (ON DELETE SET NULL — media is independent)
- **Unique:** none
- **Indexes:** `is_visible` (filter for public display), `sort_order` (ordering), `deleted_at` (soft-delete filter)
- **Soft Delete:** Yes

### 13.4 Table: `jsl_vessel_listings`

| Column | Type | Null | Default | Key | Notes | Security |
|--------|------|------|---------|-----|-------|----------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | PK | | Internal |
| public_ref_code | VARCHAR(50) | NO | | UNIQUE | e.g., "JSL-001" | Public |
| vessel_type | VARCHAR(50) | NO | | — | Enum: bulk_carrier, tugboat, barge, other | Public |
| year_built | INT | YES | NULL | — | 1900–current year | Public |
| flag_registry | VARCHAR(100) | YES | NULL | — | General registry | Public |
| gross_tonnage | DECIMAL(10,2) | YES | NULL | — | | Public |
| deadweight | DECIMAL(10,2) | YES | NULL | — | | Public |
| loa_length | DECIMAL(8,2) | YES | NULL | — | Length Overall (m) | Public |
| beam | DECIMAL(8,2) | YES | NULL | — | Width (m) | Public |
| draft | DECIMAL(8,2) | YES | NULL | — | Draft (m) | Public |
| engine_power | VARCHAR(100) | YES | NULL | — | | Public |
| trading_area | VARCHAR(255) | YES | NULL | — | General location | Public |
| marketing_description | LONGTEXT | YES | NULL | — | Rich text | Public |
| marketing_description_en | LONGTEXT | YES | NULL | — | EN (AR-24) | Public |
| real_vessel_name | VARCHAR(255) | YES | NULL | — | **SENSITIVE** | **Sensitive** |
| imo_number | VARCHAR(20) | YES | NULL | — | **SENSITIVE** | **Sensitive** |
| owner_details | TEXT | YES | NULL | — | **SENSITIVE** | **Sensitive** |
| price_commercial_terms | TEXT | YES | NULL | — | **CONFIDENTIAL** | **Confidential** |
| status | VARCHAR(20) | NO | 'open' | — | Enum: open, closed | Public |
| created_at | TIMESTAMP | YES | NULL | — | | Internal |
| updated_at | TIMESTAMP | YES | NULL | — | | Internal |
| deleted_at | TIMESTAMP | YES | NULL | — | Soft delete | Internal |

- **PK:** `id`
- **FK:** none (this is an aggregate root)
- **Unique:** `public_ref_code`
- **Indexes:** `status` (filter open/closed for public listing), `vessel_type` (filter by type), `deleted_at` (soft-delete filter), `created_at` (sort by newest)
- **Soft Delete:** Yes
- **Superseded column *(CR-001-001)*:** The original `certificates` (TEXT, nullable, Sensitive) column is **removed**. Certificate data is now stored in `jsl_vessel_certificates` (§13.6).

### 13.5 Table: `jsl_vessel_images`

| Column | Type | Null | Default | Key | Notes |
|--------|------|------|---------|-----|-------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | PK | |
| vessel_listing_id | BIGINT UNSIGNED | NO | | FK | → `jsl_vessel_listings.id` |
| media_asset_id | BIGINT UNSIGNED | NO | | FK | → `jsl_media_assets.id` |
| sort_order | INT | NO | 0 | — | Display order within listing. |
| alt_text | VARCHAR(255) | YES | NULL | — | Must NOT contain sensitive data (ADR-005). |
| created_at | TIMESTAMP | YES | NULL | — | |
| updated_at | TIMESTAMP | YES | NULL | — | |
| deleted_at | TIMESTAMP | YES | NULL | — | Soft delete. |

- **PK:** `id`
- **FK:** `vessel_listing_id` → `jsl_vessel_listings.id` (ON DELETE CASCADE for hard-delete; application-layer soft-delete cascade for soft-delete)
- **FK:** `media_asset_id` → `jsl_media_assets.id` (ON DELETE RESTRICT — media must not be deleted while referenced)
- **Unique:** none
- **Indexes:** `vessel_listing_id` (FK lookups), composite `(vessel_listing_id, sort_order)` (ordered image retrieval), `deleted_at` (soft-delete filter)
- **Soft Delete:** Yes

### 13.6 Table: `jsl_vessel_certificates` — *Added via CR-001-001*

| Column | Type | Null | Default | Key | Notes | Security |
|--------|------|------|---------|-----|-------|----------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | PK | | Internal |
| vessel_listing_id | BIGINT UNSIGNED | NO | | FK | → `jsl_vessel_listings.id` | Internal |
| certificate_type | VARCHAR(100) | NO | | — | App-validated allow-list, not a DB enum (§10.5) | **Sensitive** |
| certificate_number | VARCHAR(100) | YES | NULL | — | | **Sensitive** |
| issuing_authority | VARCHAR(255) | YES | NULL | — | | **Sensitive** |
| issue_date | DATE | YES | NULL | — | | **Sensitive** |
| expiry_date | DATE | YES | NULL | — | If set, must be ≥ issue_date (VC-10) | **Sensitive** |
| media_asset_id | BIGINT UNSIGNED | YES | NULL | FK | → `jsl_media_assets.id`. Document **must** be on `private` disk. | **Sensitive** |
| notes | TEXT | YES | NULL | — | Internal remarks | **Sensitive** |
| created_at | TIMESTAMP | YES | NULL | — | | Internal |
| updated_at | TIMESTAMP | YES | NULL | — | | Internal |
| deleted_at | TIMESTAMP | YES | NULL | — | Soft delete | Internal |

- **PK:** `id`
- **FK:** `vessel_listing_id` → `jsl_vessel_listings.id` (ON DELETE CASCADE for hard-delete; application-layer soft-delete cascade for soft-delete — same as `jsl_vessel_images`)
- **FK:** `media_asset_id` → `jsl_media_assets.id` (ON DELETE RESTRICT — media must not be deleted while referenced)
- **Unique:** none
- **Indexes:** `vessel_listing_id` (FK lookups), `expiry_date` (CMS expiry-alert queries), `deleted_at` (soft-delete filter)
- **Soft Delete:** Yes
- **Public Projection:** **None.** This table is never joined, selected, or eager-loaded by any public-facing query (ADR-005, §15).

### 13.7 Table: `jsl_gallery_items`

| Column | Type | Null | Default | Key | Notes |
|--------|------|------|---------|-----|-------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | PK | |
| media_asset_id | BIGINT UNSIGNED | NO | | FK | → `jsl_media_assets.id` |
| caption | VARCHAR(255) | YES | NULL | — | |
| category | VARCHAR(100) | YES | NULL | — | [RECOMMENDATION] (PRD §14.3). |
| caption_en | VARCHAR(255) | YES | NULL | — | EN (AR-24). |
| sort_order | INT | NO | 0 | — | Display ordering. |
| created_at | TIMESTAMP | YES | NULL | — | |
| updated_at | TIMESTAMP | YES | NULL | — | |
| deleted_at | TIMESTAMP | YES | NULL | — | Soft delete. |

- **PK:** `id`
- **FK:** `media_asset_id` → `jsl_media_assets.id` (ON DELETE RESTRICT)
- **Unique:** none
- **Indexes:** `sort_order` (ordering), `category` (filter if used), `deleted_at` (soft-delete filter)
- **Soft Delete:** Yes

### 13.8 Table: `jsl_media_assets`

| Column | Type | Null | Default | Key | Notes |
|--------|------|------|---------|-----|-------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | PK | |
| disk | VARCHAR(20) | NO | 'public' | — | 'public' or 'private'. |
| file_path | VARCHAR(500) | NO | | — | Obfuscated full path. |
| file_name | VARCHAR(255) | NO | | — | Obfuscated filename. |
| mime_type | VARCHAR(100) | NO | | — | e.g., 'image/webp', 'image/jpeg'. |
| size_bytes | INT UNSIGNED | YES | NULL | — | Original file size. |
| width | INT | YES | NULL | — | Original width (px). |
| height | INT | YES | NULL | — | Original height (px). |
| variant_thumbnail_path | VARCHAR(500) | YES | NULL | — | Thumbnail variant path. |
| variant_medium_path | VARCHAR(500) | YES | NULL | — | Medium variant path. |
| variant_large_path | VARCHAR(500) | YES | NULL | — | Large variant path. |
| created_at | TIMESTAMP | YES | NULL | — | |
| updated_at | TIMESTAMP | YES | NULL | — | |
| deleted_at | TIMESTAMP | YES | NULL | — | Soft delete. |

- **PK:** `id`
- **FK:** none (reference entity — referenced BY others)
- **Unique:** none (file_path could be unique but obfuscated filenames make collisions unlikely; unique constraint optional)
- **Indexes:** `disk` (filter by storage disk), `deleted_at` (soft-delete filter)
- **Soft Delete:** Yes

### 13.9 Table: `jsl_inquiries`

| Column | Type | Null | Default | Key | Notes | Security |
|--------|------|------|---------|-----|-------|----------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | PK | | Internal |
| name | VARCHAR(255) | NO | | — | Required | Confidential |
| company | VARCHAR(255) | YES | NULL | — | | Confidential |
| email | VARCHAR(255) | YES | NULL | — | At least one of email/phone required | Confidential |
| phone | VARCHAR(50) | YES | NULL | — | At least one of email/phone required | Confidential |
| message | TEXT | NO | | — | Required | Confidential |
| vessel_listing_id | BIGINT UNSIGNED | YES | NULL | FK | → `jsl_vessel_listings.id`. NULL for general contact. | Internal |
| consent_given | BOOLEAN | NO | FALSE | — | Privacy consent | Internal |
| status | VARCHAR(20) | NO | 'new' | — | Enum: new, read, contacted, archived | Internal |
| created_at | TIMESTAMP | YES | NULL | — | | Internal |
| updated_at | TIMESTAMP | YES | NULL | — | | Internal |
| deleted_at | TIMESTAMP | YES | NULL | — | Soft delete (after ≥ 12 months) | Internal |

- **PK:** `id`
- **FK:** `vessel_listing_id` → `jsl_vessel_listings.id` (ON DELETE SET NULL — preserve inquiry even if listing is force-deleted)
- **Unique:** none
- **Indexes:** `status` (filter for CMS inbox), `vessel_listing_id` (filter inquiries by vessel), `created_at` (sort by newest), `deleted_at` (soft-delete filter)
- **Soft Delete:** Yes (applied after ≥ 12-month retention per PRD §16.4)

### 13.10 Existing Tables (Reused — Not Created by This DBD)

| Table | Purpose | ADR/ARD Reference | Modification? |
|-------|---------|-------------------|---------------|
| `users` (existing) | CMS admin authentication via `web` guard. CMS role added via Spatie Permission. | ADR-003, ADR-008, AR-10 | ❌ No schema change. CMS role is a data entry, not a schema change. |
| Spatie `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` (existing) | Role-based authorization. CMS role and permissions are data entries. | ADR-008, AR-11 | ❌ No schema change. |
| Spatie `activity_log` (existing) | Audit log for CMS admin actions. | AR-21 | ❌ No schema change. |

### 13.11 Physical Data Model Summary

| Table | Domain | Type | Soft Delete | Rows (est. MVP) |
|-------|--------|------|-------------|-----------------|
| `jsl_company_profiles` | Content | Singleton | No | 1 |
| `jsl_site_settings` | Content | Singleton | No | 1 |
| `jsl_services` | Content | Collection | Yes | 5–20 |
| `jsl_gallery_items` | Content | Collection | Yes | 10–100 |
| `jsl_vessel_listings` | Marketing | Collection (Aggregate Root) | Yes | 10–100 |
| `jsl_vessel_images` | Marketing | Collection (Child) | Yes | 0–600 (6 per listing) |
| `jsl_vessel_certificates` *(CR-001-001)* | Marketing | Collection (Child) | Yes | 0–400 (no cap, est. 1–4 per listing) |
| `jsl_media_assets` | Media | Collection (Reference) | Yes | 50–500 |
| `jsl_inquiries` | Inquiry | Collection | Yes | 20–240/year |
| **Total new tables** | | | | **9** |

---

## 14. Media Strategy

**Reference: ADR-009 (Media Storage Strategy), ADR-005 (EXIF stripping), ARD §11 (Media Architecture)**

### 14.1 Image Ownership

| Entity | Image Relationship | Max Images | Ownership Type | FK |
|--------|-------------------|-----------|---------------|-----|
| VesselListing | Via VesselImage child entity | 6 | Composition (images owned by listing) | `jsl_vessel_images.vessel_listing_id` |
| GalleryItem | Direct reference to MediaAsset | 1 | Aggregation (references shared media) | `jsl_gallery_items.media_asset_id` |
| Service | Direct reference to MediaAsset (optional icon) | 0–1 | Aggregation (optional icon) | `jsl_services.media_asset_id` |
| CompanyProfile | Inline via WYSIWYG rich text | (no limit) | Embedded in HTML content | (no FK — images embedded in rich text via media pipeline) |
| VesselListing *(CR-001-001)* | Via VesselCertificate child entity (document, not a photo) | No cap | Composition (certificates owned by listing) | `jsl_vessel_certificates.vessel_listing_id` |

### 14.2 Gallery Relationship

Gallery items are independent aggregate roots that each reference one MediaAsset. The gallery is a simple ordered list of `jsl_gallery_items` rows, optionally filtered by `category`. There is no "gallery" parent entity — the gallery is a query result (all gallery items ordered by `sort_order`).

### 14.3 Storage Reference

All media file metadata is stored in `jsl_media_assets`. The actual files are stored on disk:

| Disk | Usage | Visibility | ADR |
|------|-------|-----------|-----|
| `public` (storage/app/public via storage:link) | Public-facing images (vessel listing photos, gallery images, service icons) | Publicly accessible via URL | ADR-009 |
| `private`/`local` (storage/app/private or similar) | Admin-only sensitive media (if any in future) | Not publicly accessible | ADR-009 |

The `disk` column in `jsl_media_assets` records which disk the file is on. The `file_path` column stores the obfuscated path relative to the disk root.

### 14.4 Maximum 6 Images per Vessel Listing — Enforcement

| Layer | Enforcement Mechanism |
|-------|----------------------|
| **Application layer (primary)** | Before accepting a new image upload for a vessel listing, the application counts existing non-deleted `jsl_vessel_images` rows for that `vessel_listing_id`. If count ≥ 6, the upload is rejected with a validation error. |
| **CMS UI (Filament)** | The Filament file upload field for vessel images displays a max-file-limit hint and prevents uploading more than the remaining slots. |
| **Database layer (optional backup)** | A CHECK constraint or trigger could be added to enforce ≤ 6 rows per `vessel_listing_id` where `deleted_at IS NULL`. This is optional and depends on MariaDB version support. The application layer is the primary enforcement. |
| **Testing** | A feature test verifies that uploading a 7th image is rejected. |

### 14.5 Image Ordering and Thumbnail

| Feature | Implementation |
|---------|---------------|
| Ordering | `sort_order` integer column in `jsl_vessel_images`. Admin can reorder via Filament drag-and-drop or sort input. |
| Thumbnail | The image with the lowest `sort_order` (i.e., `sort_order = 0` or `MIN(sort_order)`) is treated as the thumbnail for the listing index. This is a query convention, not a separate boolean flag. |
| First image | In practice, the first image (`sort_order = 1` or `0`, depending on convention) is used in the listing index card. |

### 14.6 Media Cleanup

| Scenario | Behavior |
|----------|----------|
| Vessel listing soft-deleted | Vessel images are soft-deleted (cascade soft-delete at application layer). MediaAsset rows remain (not deleted). |
| Gallery item soft-deleted | MediaAsset row remains. |
| Service soft-deleted | MediaAsset (icon) row remains. |
| Orphaned media (no referencing entities) | Scheduled cleanup task identifies MediaAssets not referenced by any non-deleted entity and soft-deletes them after a grace period. |
| Force-delete | When an entity is force-deleted, the MediaAsset is NOT force-deleted (it may be referenced by audit logs or other entities). Media force-deletion is a separate manual or scheduled operation. |

### 14.7 Certificate Document Storage — *Added via CR-001-001*

Certificate documents (PDF or image scans) follow the same `jsl_media_assets` metadata pattern as other media, with one mandatory difference:

| Rule | Detail |
|------|--------|
| Disk | Certificate document MediaAssets **must** use the `private`/`local` disk (§14.3) — **never** `public`. Certificates are classified Sensitive (PRD §15.2) and are never reachable by a public URL. |
| EXIF stripping | Still applied on upload, consistent with ADR-005/ADR-009, even though the file is never public — defense in depth. |
| Variant generation | Not required — certificate documents are not displayed as responsive web images; the original (or a single preview rendition) is sufficient for CMS viewing. |
| Access | Served only through an authenticated CMS download/preview route, never a public storage path. |

---

## 15. Security Classification

**Reference: ADR-005 (Public Projection Pattern), PRD §15.2 (Sensitive Fields), PRD AC-4**

### 15.1 Classification Levels

| Level | Description | Visible Publicly? | Visible in CMS? |
|-------|-------------|-------------------|-----------------|
| **Public** | General information; intentionally displayed on the public website. | ✅ Yes | ✅ Yes |
| **Internal** | Operational metadata; not displayed publicly but not harmful if exposed (e.g., timestamps, IDs). | ❌ No | ✅ Yes |
| **Confidential** | Personal or business-sensitive data; accessible only to CMS admin/broker. Never public. | ❌ No | ✅ Yes |
| **Sensitive** | Highest sensitivity vessel data; accessible only to CMS admin/broker. **NEVER public.** Protected by Public Projection Pattern. | ❌ No | ✅ Yes |

### 15.2 Field Classification — `jsl_vessel_listings`

| Column | Classification | Public Projection Includes? | Rationale |
|--------|---------------|----------------------------|-----------|
| id | Internal | ❌ No | Internal identifier; public uses `public_ref_code`. |
| public_ref_code | **Public** | ✅ Yes | Stable public handle for URLs and inquiry references. |
| vessel_type | **Public** | ✅ Yes | General spec. |
| year_built | **Public** | ✅ Yes | General spec. |
| flag_registry | **Public** | ✅ Yes | General registry, not owner-identifying. |
| gross_tonnage | **Public** | ✅ Yes | General spec. |
| deadweight | **Public** | ✅ Yes | General spec. |
| loa_length | **Public** | ✅ Yes | General spec. |
| beam | **Public** | ✅ Yes | General spec. |
| draft | **Public** | ✅ Yes | General spec. |
| engine_power | **Public** | ✅ Yes | General spec. |
| trading_area | **Public** | ✅ Yes | General location. |
| marketing_description | **Public** | ✅ Yes | Marketing copy. |
| marketing_description_en | **Public** | ✅ Yes (if EN enabled) | EN marketing copy. |
| real_vessel_name | **Sensitive** | ❌ **NEVER** | PRD §15.2: vessel name must never be public. |
| imo_number | **Sensitive** | ❌ **NEVER** | PRD §15.2: IMO must never be public. |
| owner_details | **Sensitive** | ❌ **NEVER** | PRD §15.2: owner must never be public. |
| price_commercial_terms | **Confidential** | ❌ **NEVER** | PRD §15.2 [RECOMMENDATION]: treat as sensitive. |
| status | **Public** | ✅ Yes | Open/Closed is public information. |
| created_at | Internal | ❌ No | Operational metadata. |
| updated_at | Internal | ❌ No | Operational metadata. |
| deleted_at | Internal | ❌ No | Soft-delete marker. |

> **Superseded (CR-001-001):** The `certificates` column previously listed here is removed. Certificate data now lives entirely in `jsl_vessel_certificates` — see §15.2a below.

### 15.2a Field Classification — `jsl_vessel_certificates` — *Added via CR-001-001*

| Column | Classification | Public Projection Includes? | Rationale |
|--------|---------------|----------------------------|-----------|
| id | Internal | ❌ No | Internal identifier; the entity itself is never public. |
| vessel_listing_id | Internal | ❌ No | FK; not exposed independently. |
| certificate_type | **Sensitive** | ❌ **NEVER** | PRD §15.2 / FR-12: certificates must never be public. |
| certificate_number | **Sensitive** | ❌ **NEVER** | Same. |
| issuing_authority | **Sensitive** | ❌ **NEVER** | Same. |
| issue_date | **Sensitive** | ❌ **NEVER** | Same. |
| expiry_date | **Sensitive** | ❌ **NEVER** | Same. |
| media_asset_id | **Sensitive** | ❌ **NEVER** | References a `private`-disk document; never a public URL. |
| notes | **Sensitive** | ❌ **NEVER** | Internal remarks. |
| created_at | Internal | ❌ No | Operational metadata. |
| updated_at | Internal | ❌ No | Operational metadata. |
| deleted_at | Internal | ❌ No | Soft-delete marker. |

> **Stronger guarantee than column-level projection:** unlike `jsl_vessel_listings`, no column on this table is ever Public. The entity itself is excluded from public queries — there is no projection to maintain because there is nothing to project.

### 15.3 Field Classification — Other Tables

| Table | Column | Classification | Notes |
|-------|--------|---------------|-------|
| `jsl_company_profiles` | All content columns | **Public** | About, overview, vision, mission are public content. |
| `jsl_company_profiles` | id, created_at, updated_at | Internal | |
| `jsl_site_settings` | site_name, tagline, footer_text, social links, contact display | **Public** | Displayed on public site. |
| `jsl_site_settings` | id, created_at, updated_at | Internal | |
| `jsl_services` | title, description, is_visible, sort_order | **Public** (when is_visible = true) | |
| `jsl_services` | id, media_asset_id, created_at, updated_at, deleted_at | Internal | |
| `jsl_gallery_items` | caption, category, sort_order | **Public** | |
| `jsl_gallery_items` | id, media_asset_id, created_at, updated_at, deleted_at | Internal | |
| `jsl_vessel_images` | sort_order | **Public** (via parent listing) | |
| `jsl_vessel_images` | alt_text | **Public** (must NOT contain sensitive data) | ADR-005 constraint. |
| `jsl_vessel_images` | id, vessel_listing_id, media_asset_id, created_at, updated_at, deleted_at | Internal | |
| `jsl_media_assets` | file_path, variant paths | **Public** (for public disk assets) | Obfuscated paths. |
| `jsl_media_assets` | id, disk, file_name, mime_type, size_bytes, width, height, timestamps | Internal | |
| `jsl_inquiries` | name, company, email, phone, message | **Confidential** | Personal data. Never public. Not shared with third parties (PRD NFR). |
| `jsl_inquiries` | vessel_listing_id, consent_given, status | Internal | |
| `jsl_inquiries` | id, created_at, updated_at, deleted_at | Internal | |
| `jsl_vessel_certificates` *(CR-001-001)* | All columns | **Sensitive** (see §15.2a) | Entity-level exclusion from public projection. |

### 15.4 Projection Compliance (ADR-005)

The Public Projection Pattern is enforced at the data-access layer:

| Rule | Enforcement |
|------|-------------|
| Public queries on `jsl_vessel_listings` select **only** Public-classified columns. | The projection object/class definition explicitly lists allowed columns. The repository's public read method uses a column selection list (allow-list), never `SELECT *`. |
| Sensitive/Confidential columns are never included in any public-facing data shape. | The projection class does not contain properties for sensitive columns. Even if a developer tries to access them, they don't exist on the projection object. |
| The full entity (with sensitive fields) is loaded **only** in CMS context. | CMS repositories use standard model hydration (all columns). Public repositories use projection mapping (selected columns only). |
| `jsl_vessel_certificates` is never eager-loaded or joined in any public query. *(CR-001-001)* | Public repositories for `VesselListing` do not include a `certificates` relation load. The relation exists only on the CMS-context model. |
| Automated leak test verifies no sensitive data in public responses. | Parameterized test across all public routes asserts absence of: real_vessel_name, imo_number, owner_details, price_commercial_terms in HTTP response bodies, **and** absence of any `jsl_vessel_certificates` data (certificate_type, certificate_number, issuing_authority, dates, notes, document URLs). |

---

## 16. Future Extension Strategy

**Reference: ADR-010 (Future Multi-Business Ready Architecture), ARD §16 (Scalability Strategy)**

### 16.1 Extension Principle

The database schema follows the same structural-readiness principle as the architecture: **future modules are additive, not modifying.** A new business unit or functional module adds new tables with its own prefix; it does not alter `jsl_` tables or the shared platform kernel.

### 16.2 Future Module Schema Extensions

| Future Module | Phase | New Tables (Prefix) | Modifies jsl_ Tables? | Modifies Platform Kernel? | ADR |
|---------------|-------|---------------------|----------------------|--------------------------|-----|
| **JSS Website** (second business unit) | Phase 5 | `jss_company_profiles`, `jss_services`, `jss_vessel_listings`, `jss_vessel_images`, `jss_gallery_items`, `jss_media_assets`, `jss_inquiries`, `jss_site_settings` | ❌ No | ❌ No | ADR-010 |
| **Group Website** (group-level branding) | Phase 5 | `group_company_profiles`, `group_services`, `group_site_settings`, etc. | ❌ No | ❌ No | ADR-010 |
| **Shipbroker / Broker Module** | Phase 2-3 | `broker_inquiry_pipelines`, `broker_deal_stages`, `broker_assignments`, etc. | ❌ No (may read `jsl_inquiries` via integration port, but no FK modification) | ❌ No | ADR-010, AR-16 |
| **Owner Portal** | Phase 3 | `owner_accounts`, `owner_vessel_drafts`, etc. | ❌ No | ❌ No (new auth guard, but no change to existing auth tables) | ADR-010 |
| **Operational Integration** | Phase 6 | Integration port (interface only; no new tables necessarily) | ❌ No | ❌ No | Requires new ADR (per supersession policy) |

### 16.3 How Extension Works Without Breaking Compatibility

```
┌───────────────────────────────────────────────────────────────────┐
│  MariaDB (Single Database)                                        │
│                                                                   │
│  jsl_ tables          jss_ tables         group_ tables           │
│  (JSL Website)        (JSS Website)       (Group Website)         │
│  ───────────          ───────────         ────────────            │
│  jsl_vessel_listings  jss_vessel_listings group_site_settings     │
│  jsl_inquiries        jss_inquiries        group_services          │
│  jsl_services         jss_services         ...                     │
│  ...                  ...                                         │
│                                                                   │
│  No FKs between jsl_ and jss_ tables.                             │
│  No FKs between jsl_ and group_ tables.                           │
│  Each unit is self-contained.                                     │
│                                                                   │
│  broker_ tables                                                   │
│  (Shipbroker Module)                                              │
│  ───────────                                                      │
│  broker_deal_stages                                               │
│  broker_assignments                                               │
│  ...                                                              │
│  May read jsl_inquiries via application-layer                     │
│  integration port (no FK, no schema coupling).                    │
│                                                                   │
│  ─────────────────────────────────────────────────────────────   │
│  Existing operational tables (vessels, shipments, voyages, etc.)  │
│  No FKs to/from any jsl_/jss_/group_ tables.                      │
│  ─────────────────────────────────────────────────────────────   │
│                                                                   │
│  Existing shared tables (users, Spatie tables, activity_log)      │
│  Reused by all modules. No schema change.                         │
└───────────────────────────────────────────────────────────────────┘
```

### 16.4 What Never Changes

| Element | Why It Never Changes |
|---------|---------------------|
| `jsl_` table schema | JSL unit is frozen; future units add their own tables. |
| `users` table | Shared auth; new modules add roles via Spatie (data, not schema). |
| Spatie tables | Shared authorization; new roles/permissions are data entries. |
| `activity_log` table | Shared audit log; new modules log via the same table. |
| Table prefix convention | Each unit uses its own prefix (`jsl_`, `jss_`, `group_`, `broker_`). |

### 16.5 i18n Extension (EN Enablement)

When the EN toggle is approved (PRD Open Question #2), the nullable `_en` columns already exist in the schema. Enabling EN is a **data population task** (filling in EN values via CMS), not a schema migration. No ALTER TABLE needed.

---

## 17. Performance Considerations

### 17.1 Expected Growth

| Table | Est. Rows at Launch | Est. Rows after 1 year | Est. Rows after 3 years | Growth Pattern |
|-------|---------------------|------------------------|------------------------|----------------|
| `jsl_company_profiles` | 1 | 1 | 1 | Static (singleton) |
| `jsl_site_settings` | 1 | 1 | 1 | Static (singleton) |
| `jsl_services` | 5–10 | 5–20 | 5–30 | Very slow growth |
| `jsl_vessel_listings` | 3–20 | 20–100 | 50–300 | Slow growth; listings closed but retained |
| `jsl_vessel_images` | 0–120 | 0–600 | 0–1800 | Proportional to listings (×6 max) |
| `jsl_vessel_certificates` *(CR-001-001)* | 5–60 | 30–300 | 100–900 | Proportional to listings (no cap, est. 1–4 each) |
| `jsl_gallery_items` | 10–30 | 20–80 | 50–200 | Slow growth |
| `jsl_media_assets` | 30–200 | 100–800 | 300–2500 | Proportional to all image-bearing entities |
| `jsl_inquiries` | 0 | 20–240 | 60–720 | Linear growth; retained ≥ 12 months |

**Volume assessment:** All tables are low-volume for MariaDB. No table exceeds a few thousand rows in the 3-year horizon. Performance optimization is precautionary, not critical.

### 17.2 Index Strategy

| Table | Index | Columns | Purpose |
|-------|-------|---------|---------|
| `jsl_vessel_listings` | `status_index` | `status` | Filter Open/Closed listings for public index page. |
| `jsl_vessel_listings` | `vessel_type_index` | `vessel_type` | Filter by vessel type (if filter/search enabled). |
| `jsl_vessel_listings` | `created_at_index` | `created_at` | Sort listings by newest first. |
| `jsl_vessel_listings` | `deleted_at_index` | `deleted_at` | Soft-delete filtering (WHERE deleted_at IS NULL). |
| `jsl_vessel_listings` | `public_ref_code_unique` | `public_ref_code` | Unique lookup by public reference code (URL route binding). |
| `jsl_vessel_images` | `vessel_listing_id_index` | `vessel_listing_id` | FK lookups (get all images for a listing). |
| `jsl_vessel_images` | `vessel_listing_id_sort_order_index` | `vessel_listing_id, sort_order` | Ordered image retrieval for a listing (composite). |
| `jsl_vessel_images` | `deleted_at_index` | `deleted_at` | Soft-delete filtering. |
| `jsl_vessel_certificates` *(CR-001-001)* | `vessel_listing_id_index` | `vessel_listing_id` | FK lookups (get all certificates for a listing). |
| `jsl_vessel_certificates` *(CR-001-001)* | `expiry_date_index` | `expiry_date` | CMS expiry-alert queries (certificates nearing/past expiry). |
| `jsl_vessel_certificates` *(CR-001-001)* | `deleted_at_index` | `deleted_at` | Soft-delete filtering. |
| `jsl_services` | `is_visible_index` | `is_visible` | Filter visible services for public page. |
| `jsl_services` | `sort_order_index` | `sort_order` | Order services for display. |
| `jsl_services` | `deleted_at_index` | `deleted_at` | Soft-delete filtering. |
| `jsl_gallery_items` | `sort_order_index` | `sort_order` | Order gallery items for display. |
| `jsl_gallery_items` | `category_index` | `category` | Filter by category (if used). |
| `jsl_gallery_items` | `deleted_at_index` | `deleted_at` | Soft-delete filtering. |
| `jsl_inquiries` | `status_index` | `status` | Filter inquiries by status for CMS inbox. |
| `jsl_inquiries` | `vessel_listing_id_index` | `vessel_listing_id` | Filter inquiries by vessel. |
| `jsl_inquiries` | `created_at_index` | `created_at` | Sort inquiries by newest first. |
| `jsl_inquiries` | `deleted_at_index` | `deleted_at` | Soft-delete filtering. |
| `jsl_media_assets` | `disk_index` | `disk` | Filter by storage disk. |
| `jsl_media_assets` | `deleted_at_index` | `deleted_at` | Soft-delete filtering. |

### 17.3 Query Patterns

| Query | Tables | Index Used | Expected Result Set | Frequency |
|-------|--------|-----------|---------------------|-----------|
| Public: list all Open vessel listings (newest first) | `jsl_vessel_listings` | `status_index` + `created_at_index` | 10–100 rows | High (every page visit, cached) |
| Public: get single vessel listing by public_ref_code | `jsl_vessel_listings` | `public_ref_code_unique` | 1 row | High (every detail page visit, cached) |
| Public: get images for a vessel listing (ordered) | `jsl_vessel_images` | `vessel_listing_id_sort_order_index` | 0–6 rows | High (every detail page visit, cached) |
| Public: list visible services (ordered) | `jsl_services` | `is_visible_index` + `sort_order_index` | 5–20 rows | Medium (cached) |
| Public: list gallery items (ordered) | `jsl_gallery_items` | `sort_order_index` | 10–100 rows | Medium (cached) |
| Public: get company profile | `jsl_company_profiles` | (PK scan, 1 row) | 1 row | Medium (cached) |
| CMS: list inquiries (newest first, filtered by status) | `jsl_inquiries` | `status_index` + `created_at_index` | 10–100 rows | Low (admin only, not cached) |
| CMS: count vessel listings | `jsl_vessel_listings` | (count with deleted_at filter) | 1 aggregate | Low (admin dashboard) |
| CMS: count inquiries | `jsl_inquiries` | (count with deleted_at filter) | 1 aggregate | Low (admin dashboard) |
| CMS: list certificates for a vessel listing *(CR-001-001)* | `jsl_vessel_certificates` | `vessel_listing_id_index` | 1–10 rows | Low (admin only, Certificates tab) |
| CMS: list certificates nearing/past expiry *(CR-001-001)* | `jsl_vessel_certificates` | `expiry_date_index` | 0–20 rows | Low (admin dashboard widget) |

### 17.4 Pagination

| Page | Pagination Strategy | Page Size |
|------|---------------------|-----------|
| Public vessel listing index | Simple pagination (Laravel `paginate()` or `simplePaginate()`) | 12–24 per page (design decision in UX-001) |
| CMS inquiry inbox | Filament table pagination | 10–25 per page (Filament default) |
| CMS vessel listings list | Filament table pagination | 10–25 per page |
| Public gallery | Simple pagination or infinite scroll | 12–24 per page |

### 17.5 Sorting

| Page | Default Sort | Sort Column |
|------|-------------|-------------|
| Public vessel listing index | Newest first | `created_at DESC` |
| Public gallery | Admin-defined order | `sort_order ASC` |
| Public services | Admin-defined order | `sort_order ASC` |
| CMS inquiry inbox | Newest first | `created_at DESC` |

### 17.6 Filtering

| Page | Filter Options | Filter Columns |
|------|---------------|----------------|
| Public vessel listing index | Status (Open/Closed), Vessel type | `status`, `vessel_type` |
| CMS inquiry inbox | Status (New/Read/Contacted/Archived) | `status` |
| CMS vessel listings | Status, Vessel type | `status`, `vessel_type` |

### 17.7 Caching Impact on Performance

Per ARD §12 and AR-17, public pages are response-cached with tag-based invalidation. This means:

| Query | Cache Hit? | DB Impact |
|-------|-----------|-----------|
| Public vessel listing index (first load) | ❌ No | Queries DB; result cached. |
| Public vessel listing index (subsequent loads) | ✅ Yes | No DB query; served from cache. |
| Public vessel detail page (first load) | ❌ No | Queries DB; result cached. |
| Public vessel detail page (subsequent loads) | ✅ Yes | No DB query; served from cache. |
| Cache invalidation (on CMS update) | — | Cache tag flushed; next load queries DB and re-caches. |

**Net effect:** DB load from public traffic is minimal due to caching. CMS queries are low-frequency (admin only). The database is well within capacity for the MVP and 3-year growth horizon.

---

## 18. Developer Guidelines

### 18.1 Database Rules Developers Must Follow

| # | Rule | Source |
|---|------|--------|
| DR-1 | All new tables for the JSL website module MUST use the `jsl_` prefix. | ADR-006, ARD R-5 |
| DR-2 | No `jsl_` table may have a foreign key referencing an operational table (e.g., `vessels`, `shipments`, `branches`). | ADR-006, ARD C-10 |
| DR-3 | No operational table may be altered to reference a `jsl_` table. | ADR-006, ARD C-10 |
| DR-4 | The `users` table and Spatie tables are reused; do NOT create new auth tables. | ADR-003, ADR-008, AR-10 |
| DR-5 | The CMS role is a Spatie Permission data entry; do NOT create a new role table. | ADR-008 |
| DR-6 | Migrations for `jsl_` tables must be in the website module's migration directory, not the default `database/migrations/`. | ADR-002 (module isolation) |
| DR-7 | Every table must have `created_at` and `updated_at` timestamp columns. | Convention |
| DR-8 | Soft-deletable tables must have a `deleted_at` nullable timestamp column. | PRD §13, AR-25 |
| DR-9 | Enum values are stored as lowercase strings (e.g., 'open', 'closed', 'new'). | Convention |
| DR-10 | Foreign key columns use `<singular_entity>_id` naming. | Convention |
| DR-11 | All media file metadata goes in `jsl_media_assets`; do NOT store file paths directly in `jsl_vessel_listings` or other entity tables (except via FK to `jsl_media_assets`). | ADR-009, ARD R-10 |
| DR-12 | The `jsl_company_profiles` and `jsl_site_settings` tables are singletons; migrations must enforce or seed a single row. | AR-22 |
| DR-13 *(CR-001-001)* | Certificate document MediaAssets (`jsl_vessel_certificates.media_asset_id`) must always use the `private` disk; never `public`. | PRD §15.2, ADR-005, ADR-009 |

### 18.2 Forbidden Practices

| # | Forbidden Practice | Reason | ADR/ARD |
|---|-------------------|--------|---------|
| DF-1 | ❌ Creating a foreign key from a `jsl_` table to an operational table. | Cross-domain data coupling. | ADR-006, ARD C-10 |
| DF-2 | ❌ Using `SELECT *` in public-facing queries on `jsl_vessel_listings`. | Would include sensitive columns. Must use explicit column allow-list. | ADR-005, ARD C-2 |
| DF-3 | ❌ Storing EXIF data or metadata in `jsl_media_assets`. | EXIF must be stripped before storage. | ADR-005, ADR-009, ARD C-14 |
| DF-4 | ❌ Creating a table without the `jsl_` prefix for JSL website data. | Violates naming convention and logical separation. | ADR-006, ARD R-5 |
| DF-5 | ❌ Altering the existing `users` table schema for CMS purposes. | CMS reuses existing auth; role is data, not schema. | ADR-003, ADR-008 |
| DF-6 | ❌ Hardcoding sensitive field values in migrations or seeders. | Sensitive data must be entered via CMS, not code. | ADR-005 |
| DF-7 | ❌ Creating a separate `vessels`-like table that mirrors the operational `vessels` table. | The marketing VesselListing is a distinct domain entity (ADR-004), not a copy of operational Vessel. | ADR-004, ARD C-1 |
| DF-8 | ❌ Using integer codes for enum values instead of strings. | Strings are self-documenting in the database; aligns with Laravel enum handling. | Convention |
| DF-9 | ❌ Storing broker email or broker WhatsApp number in `jsl_site_settings`. | Those are secrets/environmental values; they belong in `.env`. Only display values go in the DB. | AR-22, ARD §14.2 |
| DF-10 | ❌ Creating more than one row in `jsl_company_profiles` or `jsl_site_settings`. | These are singletons. | AR-22 |
| DF-11 *(CR-001-001)* | ❌ Storing a certificate document's MediaAsset on the `public` disk, or eager-loading `jsl_vessel_certificates` in any public-facing query/controller. | Certificates are Sensitive and must never be reachable by a public URL or appear in public responses. | PRD §15.2, ADR-005, ADR-009 |

### 18.3 Migration Rules

| # | Rule | Reason |
|---|------|--------|
| MR-1 | Migrations for `jsl_` tables are owned by the website module and placed in the module's migration directory. | Module isolation (ADR-002). |
| MR-2 | Migrations must not modify operational table schemas. | Boundary preservation (ADR-002, ADR-006). |
| MR-3 | Migrations must not create foreign keys to operational tables. | Boundary preservation (ADR-006). |
| MR-4 | Each migration creates one or more `jsl_` tables with correct prefix, columns, constraints, and indexes per this DBD. | Blueprint compliance. |
| MR-5 | Seeders (if used for development) must not include real sensitive data. | Security (ADR-005). |
| MR-6 | The migration for `jsl_company_profiles` and `jsl_site_settings` should seed a default empty row. | Singleton initialization. |
| MR-7 | Rollback (down) migrations must drop only `jsl_` tables, never operational tables. | Safety. |

### 18.4 Naming Rules Summary

| Element | Rule | Example |
|---------|------|---------|
| Table | `jsl_` + snake_case plural | `jsl_vessel_listings` |
| Column | snake_case | `public_ref_code` |
| PK | `id` (BIGINT UNSIGNED, auto-increment) | `id` |
| FK column | `<singular_entity>_id` | `vessel_listing_id` |
| FK constraint | `<table>_<column>_foreign` | `jsl_vessel_images_vessel_listing_id_foreign` |
| Index | `<table>_<column>_index` | `jsl_vessel_listings_status_index` |
| Unique | `<table>_<column>_unique` | `jsl_vessel_listings_public_ref_code_unique` |
| Enum values | lowercase string | `open`, `closed`, `new` |
| EN column | `<field>_en` | `title_en` |
| Timestamp | `<event>_at` | `created_at`, `deleted_at` |
| Boolean | `is_<adj>` or `<verb>_given` | `is_visible`, `consent_given` |

---

## 19. Database Traceability Matrix

**Map: Requirement → Entity → Relationship → Constraint**

| Requirement | Entity (Table) | Relationship | Constraint | Source |
|-------------|----------------|--------------|------------|--------|
| FR-01 Company Profile | `jsl_company_profiles` | (singleton, no relationships) | Singleton (1 row); rich text fields; nullable EN fields | PRD FR-01, ADR-007, ARD §8.2 |
| FR-02 Services Display | `jsl_services` | Service → MediaAsset (M:1, optional icon) | is_visible boolean; sort_order; soft-delete; nullable EN | PRD FR-02, ADR-007, ADR-008 |
| FR-03 Vessel Trading (public listing) | `jsl_vessel_listings` | VesselListing → VesselImage (1:M, max 6) | public_ref_code UNIQUE; status enum (open/closed); default 'open'; soft-delete; sensitive fields classified | PRD FR-03, ADR-004, ADR-005 |
| FR-03 Vessel images | `jsl_vessel_images` | VesselImage → VesselListing (M:1, NOT NULL); VesselImage → MediaAsset (M:1, NOT NULL) | Max 6 per listing (app layer); sort_order; alt_text (no sensitive data) | PRD §15.4, ADR-009 |
| FR-04 Inquiry per vessel (form) | `jsl_inquiries` | Inquiry → VesselListing (M:1, nullable) | name required; email or phone required (app layer); message required; consent_given boolean | PRD FR-04, §16.1, AR-15 |
| FR-04 Inquiry (WhatsApp/Email) | (no table — client-side only) | N/A | N/A — WA/Email are client-side links, no server record | PRD §16.3, AR-15 |
| FR-05 General Contact | `jsl_inquiries` | Inquiry → VesselListing (M:1, nullable = NULL for general) | vessel_listing_id NULL for general contact | PRD FR-05, §16.2 |
| FR-06 Gallery | `jsl_gallery_items` | GalleryItem → MediaAsset (M:1, NOT NULL) | caption nullable; category nullable; sort_order; soft-delete; nullable EN | PRD FR-06, §14.3, ADR-009 |
| FR-07 CMS Authentication | Existing `users` + Spatie tables | (reused, no new tables) | CMS role via Spatie Permission (data entry); no ScopeByBranch | PRD FR-07, ADR-003, ADR-008 |
| FR-08 CMS Vessel Listing Management | `jsl_vessel_listings`, `jsl_vessel_images`, `jsl_media_assets` | VesselListing → VesselImage (composition); VesselImage → MediaAsset (aggregation) | CRUD; max 6 images; status toggle Open/Closed; sensitive fields in same table (projection isolates) | PRD FR-08, §15, ADR-004, ADR-005 |
| FR-09 CMS Inquiry Inbox | `jsl_inquiries` | (standalone, optional ref to VesselListing) | status enum (new/read/contacted/archived); soft-delete after 12 months | PRD FR-09, §16.4 |
| FR-12 CMS Vessel Certificate Management *(CR-001-001)* | `jsl_vessel_certificates` | VesselCertificate → VesselListing (M:1, NOT NULL, composition); VesselCertificate → MediaAsset (M:1, nullable, `private` disk) | CRUD; no max count; all columns Sensitive; entity excluded from public projection entirely | PRD FR-12, §15.2, AC-17, ADR-004, ADR-005, ADR-009 |
| AC-4 No sensitive data public | `jsl_vessel_listings`, `jsl_vessel_certificates` | (projection excludes sensitive columns / excludes entity) | Sensitive fields: real_vessel_name, imo_number, owner_details, price_commercial_terms — NEVER in public queries; `jsl_vessel_certificates` — entity never joined/selected publicly | PRD AC-4, ADR-005 |
| AC-5 Open/Closed status | `jsl_vessel_listings` | (status column) | status enum: 'open', 'closed'; default 'open'; public visibility for both; inquiries disabled when 'closed' | PRD AC-5, §15.3 |
| Media storage | `jsl_media_assets` | (referenced by VesselImage, GalleryItem, Service) | disk (public/private); obfuscated file_path; variant paths; EXIF stripped (app layer); soft-delete | PRD §14.5, §15.4, ADR-009 |
| Marketing-editable site settings | `jsl_site_settings` | (singleton, no relationships) | Singleton (1 row); social links, brand text, display contact; nullable EN | AR-22, ARD §14.2 |
| Audit log | Existing Spatie `activity_log` | (reused, no new table) | CMS admin actions logged; no schema change | PRD §13, AR-21 |
| Soft-delete vessels | `jsl_vessel_listings` | (deleted_at column) | deleted_at nullable timestamp; restorable | PRD §13 |
| Soft-delete inquiries | `jsl_inquiries` | (deleted_at column) | deleted_at nullable timestamp; applied after ≥ 12 months | PRD §13, §16.4 |
| i18n readiness | All content tables | (nullable _en columns) | Nullable EN columns on: company_profiles, services, vessel_listings, gallery_items, site_settings | AR-24 |
| No operational integration | (no cross-domain FKs) | (no FKs between jsl_ and operational tables) | Enforced by schema design + CI | ADR-002, ADR-006, AR-16 |

---

## 20. Glossary

| Term | Definition | Source |
|------|-----------|--------|
| **DBD** | Database Design Document. This document. | — |
| **Aggregate Root** | An entity that serves as the entry point to an aggregate cluster. Child entities are accessed through the root. | DDD concept, ARD §6 |
| **Child Entity** | An entity that belongs to an aggregate root and cannot exist independently. E.g., VesselImage is a child of VesselListing. | ARD §6 |
| **Reference Entity** | An independent entity referenced by other entities via aggregation (not composition). E.g., MediaAsset. | ARD §6 |
| **Composition** | A relationship where the child cannot exist without the parent. Strong ownership. | §7 |
| **Aggregation** | A relationship where the referenced entity exists independently. Weak ownership. | §7 |
| **Singleton Entity** | An entity with exactly one row in its table. E.g., CompanyProfile, SiteSettings. | §5 |
| **Public Projection** | A read shape that structurally excludes sensitive fields from public-facing code paths. | ADR-005, ARD §13.7 |
| **Sensitive Fields** | Vessel real name, IMO number, owner details. Never exposed publicly. Certificates are a wholly sensitive child entity (`jsl_vessel_certificates`) since CR-001-001. | PRD §15.2, ADR-005 |
| **VesselCertificate** *(CR-001-001)* | A child entity of VesselListing storing one internal certificate record (type, number, issuing authority, dates, document, notes). Supersedes the original free-text `certificates` column. Wholly Sensitive; never public. | §5.2.3, §15.2a |
| **Confidential Fields** | Personal data (inquiry name, email, phone) or commercial terms. Never public. | §15 |
| **Public Fields** | General information fields intentionally displayed on the public website. | §15 |
| **Internal Fields** | Operational metadata (IDs, timestamps) not displayed publicly but not harmful. | §15 |
| **Soft Delete** | Setting `deleted_at` timestamp instead of physically removing a row. Allows recovery. | PRD §13 |
| **`jsl_` Prefix** | Table naming prefix for all Jaya Sakti Line website module tables. Ensures logical separation from operational tables. | ADR-006, ARD R-5 |
| **EXIF Stripping** | Removal of EXIF/metadata from uploaded image files before storage. Prevents owner/vessel identity leakage. | ADR-005, ADR-009 |
| **Responsive Variants** | Multiple sizes of an image (thumbnail, medium, large) generated on upload. | ADR-009, ARD §11 |
| **Obfuscated Filename** | A non-guessable filename (UUID or random string) assigned to stored media files. | ADR-009 |
| **Media Abstraction** | A service/interface that encapsulates filesystem and image-processing concerns. | ADR-009, ARD §11.2 |
| **`public_ref_code`** | A stable, public, unique reference code for a vessel listing (e.g., "JSL-001"). Used in URLs and inquiry references. Not the real vessel name. | PRD §15.1 |
| **i18n Readiness** | The schema includes nullable EN translation columns so enabling English is a data-population task, not a schema migration. | AR-24 |
| **Site Settings** | A singleton record storing marketing-editable site-wide values (social links, brand text, display contact info). Distinct from `.env` secrets. | AR-22 |
| **Leak Test** | An automated test asserting absence of sensitive fields in all public route responses. | ADR-005, AR-23 |
| **Integration Port** | A documented seam for future integration with the operational system. Not implemented in MVP. | ADR-010, AR-16 |
| **3NF** | Third Normal Form. No transitive dependencies; all non-key attributes depend only on the primary key. | §11 |
| **Denormalization** | Deliberate departure from normal form for simplicity or performance, with documented rationale. | §11.4 |

---

**End of DBD-001 — Database Design Document (v1.1.0).**

This document is the official database blueprint for the Jaya Sakti Line Website MVP. It derives solely from accepted ADRs, the approved ARD, and the frozen PRD-001 (now v1.1.0, amended via approved CR-001-001). It introduces no new architecture decisions and no business requirements of its own — `jsl_vessel_certificates` (§5.2.3) translates FR-12, which originates in PRD-001. The next document is **UX-001 — UI/UX Specification**, which will define wireframes, user flows, and the design system consistent with the ADRs, ARD, and this DBD — including a Certificates tab on the Vessel Listings manager per CR-001-001.
