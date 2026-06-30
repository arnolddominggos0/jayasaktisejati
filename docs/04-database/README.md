# 04 — Database Design (DBD)

Entity design, schema, data model, migration plan, and sensitive-data handling.

## Current Documents

| Document | Title | Version | Status |
|----------|-------|---------|--------|
| `DBD-001-JSL-Website-MVP.md` | Database Design Document — JSL Website MVP | 1.1.0 | ✅ APPROVED — Database Blueprint |

## Workflow

1. **DBD-001** — Define data model, table structure, naming conventions, and migration plan consistent with the ADRs and ARD. ✅ Approved.

## Change Requests Applied

| CR ID | Title | Status | DBD-001 Change |
|-------|-------|--------|-----------------|
| [CR-001-001](../02-product/CR-001-001-Internal-Vessel-Certificate-Management.md) | Internal Vessel Certificate Management | ✅ Approved | Added `jsl_vessel_certificates` entity (§5.2.3). Superseded the free-text `certificates` column on `jsl_vessel_listings`. Bumped to v1.1.0. |

## Status

**Status:** DBD-001 approved (v1.1.0). UX-001 — UI/UX Specification approved (v1.1.0) under `docs/05-uiux/`. Next phase: Sprint Planning under `docs/06-sprint/`.
