# 03 — Architecture Design (ARD)

System architecture, components, tech stack, integrations, and security design.

## Current documents

| Document | Title | Version | Status |
|----------|-------|---------|--------|
| `ARCHITECTURE_REVIEW.md` | Architecture Review (precursor to ADR-001) | 0.1.0 | ✅ Accepted by ARB |
| `ADR-001.md` | Architecture Decision Records (ADR-001 → ADR-010) | 1.0.0 | ✅ ACCEPTED — Architectural Baseline |
| `ARD-001-JSL-Website-MVP.md` | Architecture Design Document (Implementation Blueprint) | 1.0.0 | ✅ APPROVED — Ready to Freeze |
| `ARCHITECTURE_WALKTHROUGH.md` | Architecture Walkthrough (Audit Report) | 1.0.0 | ✅ Complete — ⚠ Approved with Notes |
| `DBD-001-JSL-Website-MVP.md` (in `docs/04-database/`) | Database Design Document — JSL Website MVP | 1.1.0 | ✅ APPROVED |

## Workflow

1. **Architecture Review** (`ARCHITECTURE_REVIEW.md`) — identify every architecture decision, options, trade-offs, recommendation. Reviewed and accepted by the ARB. ✅
2. **Architecture Decision Records** (`ADR-001.md`) — formalize the accepted decisions into immutable ADRs (the architectural constitution). ✅ Accepted.
3. **Architecture Design Document** (`ARD-001-JSL-Website-MVP.md`) — transform accepted ADRs into the implementation blueprint. Must not contradict any Accepted ADR. ✅ Approved.
4. **Architecture Walkthrough** (`ARCHITECTURE_WALKTHROUGH.md`) — final architecture gate audit before DBD-001. Validates PRD coverage, ADR compliance, consistency, security, scalability, maintainability, developer readiness, and risks. ✅ Complete — ⚠ Approved with Notes.
5. **DBD-001** (in `docs/04-database/`) — database design derived from ADRs and ARD. ✅ Approved.

## Status

**Status:** Architecture phase complete. All 10 ADRs accepted; ARD-001 approved with notes; DBD-001 approved (v1.1.0, amended via approved [CR-001-001](../02-product/CR-001-001-Internal-Vessel-Certificate-Management.md)). UX-001 — UI/UX Specification approved (v1.1.0) under `docs/05-uiux/`. Next phase: Sprint Planning under `docs/06-sprint/`. PRD-001 remains frozen (✅ frozen as of 2026-06-30, amended to v1.1.0 via approved Change Requests).
