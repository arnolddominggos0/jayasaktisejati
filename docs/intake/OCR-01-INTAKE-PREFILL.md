# OCR-01 — IntakePrefill Architecture

Status: IMPLEMENTED (Sprint OCR-01)
Scope: architectural layer only — no OCR quality work, no UI, no workflow change.

## Why

Architecture review (SPPB Intake Experience) froze these decisions:

- OCR is an **assistant**, never a creator. Shipment is authored by Office Admin.
- Extraction output is a **claim about a document**; form content is an
  **assertion by the admin**. The two must never share a container silently.
- Every intake channel (SPPB, manual, Excel, API, portal) must converge on
  one channel-neutral envelope feeding the same create wizard.

The old pipeline violated all three by writing extraction results directly
into Livewire form state.

## Current Flow (before OCR-01)

```
Upload PDF
→ FileUpload::afterStateUpdated()
→ SppbAssistService::assist($state, $get, $set)
   → extract(): array of field suggestions
   → apply(): writes into form state immediately   ← the problem
→ Shipment form silently prefilled
```

## New Flow (OCR-01)

```
Upload PDF
→ FileUpload::afterStateUpdated()
→ SppbAssistService::assist($state)
   → extract(): IntakePrefill (immutable envelope)
→ $livewire->intakePrefill = envelope              ← held, NOT applied
→ (wait)
→ OCR-02: Review Summary renders from the envelope (summaryItems())
→ OCR-03: explicit "Terapkan" invokes apply() with envelope fields
→ Shipment form
```

## The envelope — `App\Support\Intake\IntakePrefill`

Immutable (readonly), Livewire `Wireable`, framework-pure (no app boot needed).

| Block | Contents | Species |
|---|---|---|
| `source` | channel, artifacts, received_at | provenance |
| `document` | number, date (+confidences) | artifact identity |
| `copyFields` | scalar values (delivery_scope, notes, …) | safe prefill on Apply |
| `manifest` | detected_count + unit rows | cardinality check before insert |
| `suggestions` | customer_id / receiver_id / destination_city_id (+match label) | entity resolution — NEVER auto-applied as links |
| `warnings` | code + message list | honest gaps |

Helpers ready for OCR-02 (no re-extraction needed): `summaryItems()`,
`detectedFieldCount()`, `unitCount()`, `hasWarnings()`, `suggestionFor()`,
`isEmpty()`, `IntakePrefill::empty()`.

Channel-neutral by contract: no SPPB-specific naming; future channels
(manual/Excel/API/portal) produce this same object.

## Changed files

- `app/Support/Intake/IntakePrefill.php` — NEW: the envelope DTO.
- `app/Services/SppbAssistService.php` — `extract()` returns IntakePrefill;
  regex/match phases moved verbatim to `extractSuggestionsFromText()`
  (patterns and confidences untouched); `buildPrefill()` classifies fields;
  `assist()` returns the envelope and no longer calls `apply()`; `apply()`
  retained unchanged for OCR-03.
- `app/Filament/Resources/ShipmentResource.php` — upload closure stores the
  envelope on the page (`$livewire->intakePrefill`), writes nothing to form
  state; `_sppb_assisted_fields` Hidden removed (was write-only).
- `app/Filament/Resources/ShipmentResource/Pages/CreateShipment.php` —
  `public ?IntakePrefill $intakePrefill` holder.
- `tests/Unit/IntakePrefillTest.php` — envelope contract tests (4 passing).

## Backward compatibility

- Manual entry: unchanged (no upload → no extraction → null envelope).
- Empty/failed extraction: envelope with warnings; form untouched — wizard
  behaves exactly like manual entry.
- Note: the old pipeline in practice produced no visible prefill anyway
  (extraction yielded nothing on real SPPBs), so removing auto-apply changes
  no observed behavior.
- Edit page: has no envelope holder → extraction skipped entirely (intake is
  a creation concern).

## Next sprints

- OCR-02 — Review Summary UI (renders `summaryItems()`; Terapkan/Abaikan).
- OCR-03 — Explicit Apply (copyFields → form; manifest → unit rows;
  suggestions → pre-focused selects; voyage hints → voyage step context).
- Sprint 1.7 — unit-table extraction fills `manifest` (envelope unchanged).
