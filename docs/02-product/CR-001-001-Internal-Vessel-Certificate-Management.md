# Change Request (CR)
## CR-001-001 — Internal Vessel Certificate Management

**Project:** Jaya Sakti App
**Document:** CR-001-001 (Change Request against PRD-001)
**Version:** 1.0.1
**Status:** ✅ APPROVED
**Document Owner:** Product Team
**Last Updated:** 2026-06-30
**Raised Against:** `docs/02-product/PRD-001-JSL-Website-MVP.md` (v1.0.0, Frozen)
**Naming Convention:** `CR-<PRD-ID>-<NNN>` per `docs/README.md` §Scope Control & Change Management.

> **Governance Rule**
> PRD-001 is frozen. This CR is the formal, approved mechanism by which its scope is extended. Approval of this CR authorizes PRD-001 to be revised to v1.1.0 and DBD-001 to be revised to v1.1.0. It does **not** reopen any other part of either document, and it introduces **no new architecture decision** (no new/superseded ADR required).

---

## Change History

| Version | Date | Author | Description |
|---------|------|--------|-------------|
| 1.0.0 | 2026-06-30 | Product Team | Initial CR — raised and approved. |
| 1.0.1 | 2026-06-30 | Product Team | Updated §3 Impact and §6 Resolution: UX-001 was authored concurrently and now includes the Certificates tab (§12.6a) directly, rather than carrying a queued note. |

---

## 1. Title

Internal Vessel Certificate Management

## 2. Reason

The business owner requires certificate management for each vessel listing. Certificates are for **internal CMS use only** and must **never** be exposed on the public website.

This formalizes and extends the existing sensitive `certificates` free-text field on the vessel listing (PRD-001 §15.2) into a proper, structured, internal-only record set — supporting multiple certificates per vessel (e.g., Certificate of Registry, Classification Certificate, Safety Management Certificate), each with type, number, issuing authority, issue/expiry dates, an optional document upload, and internal notes.

## 3. Impact

| Area | Impact |
|------|--------|
| **PRD** | Update to **v1.1.0** — new FR-12 (CMS — Vessel Certificate Management), Feature Scope, Sitemap, CMS Requirements, Acceptance Criteria. |
| **DBD** | Update to **v1.1.0** — add `jsl_vessel_certificates` entity (child of `VesselListing`, Marketing Domain). The legacy free-text `certificates` column on `jsl_vessel_listings` is superseded by this entity. |
| **UX** | Update to **v1.1.0** — added a **Certificates tab** (§12.6a) to the Vessel Listing Editor in the CMS. |
| **Architecture** | **None.** The new entity follows the existing composition pattern already established for `VesselImage` (ADR-004 Separate Marketing Domain Model) and is covered by the existing Public Projection Pattern (ADR-005) and Media Storage Strategy (ADR-009) without modification. No ADR is opened or superseded. |

## 4. Priority

High

## 5. Status

Approved

---

## 6. Resolution

This CR was implemented entirely at the documentation layer (no code/migrations exist yet for the JSL Website module — development has not started).

| Deliverable | Change |
|-------------|--------|
| PRD-001 | Bumped to v1.1.0. Added **FR-12 CMS — Vessel Certificate Management** [CONFIRMED]. Updated §8.1 Feature Scope, §10 Sitemap, §13 CMS Requirements, §15.2 Sensitive Fields, §17 Acceptance Criteria (AC-17), Appendix B. |
| DBD-001 | Bumped to v1.1.0. Added `jsl_vessel_certificates` entity (§5.2.3), updated Traceability (§3), Business Domains (§4), Aggregate Design (§6), Relationship Design (§7, R-8/R-9), Entity Lifecycle (§8.7), Constraints (§9), Naming Convention (§10), Logical ERD (§12), Physical Data Model (§13.4a new table + §13.10 summary), Media Strategy (§14.7), Security Classification (§15), Database Traceability Matrix (§19), Glossary (§20). Removed the superseded free-text `certificates` column from `jsl_vessel_listings` (§5.2.1, §12, §13.4, §15.2). |
| UX-001 | Bumped to v1.1.0. Added **Certificates Tab** wireframe (§12.6a) on the Vessel Listing Editor — certificate list with expiry highlighting, and a create/edit form (type, number, issuing authority, dates, private document upload, notes). Removed the superseded free-text "Certificates" field from the Sensitive section (§12.6). Updated the public-wireframe "NEVER shown" notes (§6), CMS Page Map (§11.4), UX Constraints (§15.1), UX Traceability Matrix (§16), and Glossary (§18). |
| Doc Index | `docs/README.md`, `docs/03-architecture/README.md`, `docs/04-database/README.md`, `docs/05-uiux/README.md` updated to reflect new versions and this CR. |

No public-facing scope changed. The sensitive-data boundary (PRD AC-4 / ADR-005) is preserved: certificate data and documents remain internal-only, never selected by the public projection.

---

**End of CR-001-001.**
