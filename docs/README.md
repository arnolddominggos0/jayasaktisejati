# Jaya Sakti App — Documentation Index

This is the official documentation foundation for the **Jaya Sakti App** project.

The first module under this project is the **Jaya Sakti Line Website MVP** (Company Profile & Vessel Trading).

---

## Documentation Lifecycle

All project documentation follows this sequential lifecycle. A downstream document must not be started until its upstream predecessor is approved/frozen.

```
Discovery
   ↓
Business Requirement (BRD)
   ↓
Product Requirement (PRD)
   ↓
Architecture Design (ARD)
   ↓
Database Design (DBD)
   ↓
UI/UX Specification
   ↓
Sprint Planning
   ↓
Development
   ↓
Testing
   ↓
Release
```

No implementation begins until the PRD is frozen and the ARD is completed.

---

## Folder Structure

| Folder | Stage | Purpose |
|--------|-------|---------|
| `00-discovery/` | Discovery | Market research, user research, competitive analysis, problem framing. |
| `01-business/` | Business Requirement (BRD) | Business goals, stakeholder needs, business constraints. |
| `02-product/` | Product Requirement (PRD) | Product scope, features, acceptance criteria, success metrics. **Source of truth for the MVP.** |
| `03-architecture/` | Architecture Design (ARD) | System architecture, components, tech stack, integrations, security design. |
| `04-database/` | Database Design (DBD) | Entity design, schema, data model, migration plan, sensitive-data handling. |
| `05-uiux/` | UI/UX Specification | Wireframes, user flows, design system, responsive spec, accessibility. |
| `06-sprint/` | Sprint Planning (+ Development, Testing) | Sprint plans, task breakdown, development logs, test plans, QA records. |
| `07-release/` | Release | Release notes, deployment records, launch checklist, post-launch review. |

---

## Current Documents

| Document ID | Title | Location | Version | Status |
|-------------|-------|----------|---------|--------|
| PRD-001 | Jaya Sakti Line Website MVP | `02-product/PRD-001-JSL-Website-MVP.md` | 1.0.0 | ✅ FROZEN |

### Next phase
- **ARD-001 — Architecture Design Document** → to be created under `03-architecture/`.

---

## Scope Control & Change Management

The PRD is the **Single Source of Truth (SSOT)** for the MVP.

- The PRD is **frozen**. No scope changes are allowed inside the PRD directly.
- Any feature request, enhancement, or business change must be documented as a **Change Request (CR)** before implementation.
- The development team must not modify MVP scope directly.
- Each CR is reviewed and approved by business stakeholders before it can affect the backlog.

Change Request naming convention: `CR-<PRD-ID>-<NNN>` (e.g., `CR-001-001`).

---

## Document Conventions

- Every formal document carries a header block: **Project, Document, Version, Status, Document Owner, Last Updated, Next Phase**.
- Every formal document includes a **Change History** table.
- Requirement items are tagged:
  - **[CONFIRMED]** — committed to the MVP.
  - **[RECOMMENDATION]** — proposed by Product, pending stakeholder approval.
  - **[FUTURE]** — explicitly out of MVP scope; roadmap only.

---

## Note on Pre-existing Documentation

The `docs/` directory already contains documentation for a **separate, pre-existing product** — the Operational Logistics & Distribution System (e.g., `docs/PRD.md`, `docs/voyage-monitoring/`, `docs/pelacakan-monitoring/`, `docs/tam-demo/`, `docs/FC_*.md`, `docs/SCOPING.md`).

Those documents are **NOT part of the Jaya Sakti Line Website MVP**. Do not mix scopes. The JSL Website MVP scope is defined solely by `02-product/PRD-001-JSL-Website-MVP.md`.

---

## Version

| Version | Date | Author | Description |
|---------|------|---------|-------------|
| 1.0.0 | 2026-06-30 | Product Team | Initial documentation foundation (PRD-001 frozen). |
