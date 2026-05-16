# Source of Truth

| Data | Owner |
|---|---|
| ETD | voyages.etd |
| ETA | voyages.eta |
| OTB | computed from voyage |
| Delay Reason | voyages.manual_delay_reason |
| Milestones | voyage_milestones |

## Rules

- voyages adalah central operational entity
- KPI tidak boleh diinput manual
- readiness checkpoints auto-generated