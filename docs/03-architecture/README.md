# 03 — Architecture Design (ARD)

System architecture, components, tech stack, integrations, and security design.

## Current documents

| Document | Title | Version | Status |
|----------|-------|---------|--------|
| `ARCHITECTURE_REVIEW.md` | Architecture Review (precursor to ADR-001) | 0.1.0 | ✅ Accepted by ARB |
| `ADR-001.md` | Architecture Decision Records (ADR-001 → ADR-010) | 1.0.0 | ✅ ACCEPTED — Architectural Baseline |
| `ARD-001-JSL-Website-MVP.md` | Architecture Design Document (Implementation Blueprint) | 1.0.0 | ✅ APPROVED — Ready to Freeze |
| `ARCHITECTURE_WALKTHROUGH.md` | Architecture Walkthrough (Audit Report) | 1.0.0 | ✅ Complete — ⚠ Approved with Notes |
| DBD-001 (to be created) | Database Design Document | — | Pending |

## Workflow

1. **Architecture Review** (`ARCHITECTURE_REVIEW.md`) — identify every architecture decision, options, trade-offs, recommendation. Reviewed and accepted by the ARB. ✅
2. **Architecture Decision Records** (`ADR-001.md`) — formalize the accepted decisions into immutable ADRs (the architectural constitution). ✅ Accepted.
3. **Architecture Design Document** (`ARD-001-JSL-Website-MVP.md`) — transform accepted ADRs into the implementation blueprint. Must not contradict any Accepted ADR. ✅ Approved.
4. **Architecture Walkthrough** (`ARCHITECTURE_WALKTHROUGH.md`) — final architecture gate audit before DBD-001. Validates PRD coverage, ADR compliance, consistency, security, scalability, maintainability, developer readiness, and risks. ✅ Complete — ⚠ Approved with Notes.
5. **DBD-001 — Database Design Document** — define data model, table structure, naming conventions, and migration plan consistent with the ADRs and ARD. Pending.

## Status

**Status:** Architecture Review in progress — do not start ARD-001 until the review is approved by the ARB and PRD-001 remains frozen (✅ frozen as of 2026-06-30).
