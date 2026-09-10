# ADR-002 — Penalty savings program: staged rollout, no feature flags

**Status:** Accepted
**Date:** 2026-05-01

## Context

Historical RR-doc backfill shows a `~₹1.34 Cr` actual penalty pool already billed by Indian Railways. Distribution: 85% demurrage (DEM), 15% penal loading overcharge (PLO), <1% combined POL1/POLA/ENHC. Predictive coverage in code today inverts this — POL1/POLA are implemented, DEM only barely produces output, PLO not at all. `rr_penalty_snapshots` (billed) and `applied_penalties` (predicted) sit side-by-side with no reconciliation layer. Loadrite hardware is partially deployed (Dumka).

## Decision

Deliver penalty savings as a three-stage program documented in `docs/superpowers/specs/2026-05-01-penalty-savings-program-design.md`:

- Stage 1 — predicted-vs-billed reconciliation, PLO calculator, Pakur data-capture, calibration corpus
- Stage 2 — Loadrite live ingestion + WhatsApp alert channel
- Stage 3 — AI dispute factory

Each stage is independently shippable. Activation is data-driven (Loadrite settings row presence) and policy-gated (`DisputePolicy`). **No Pennant feature flags.** Calibration corpus is a hard CI gate at Stage-1 merge. Rollback = `git revert`; all migrations stay additive.

## Consequences

**Easier:**
- Each stage compounds value; Stage 1 alone moves the dial because demurrage (85%) and PLO (15%) become predicted and reconciled.
- No flag-machinery overhead. Single source of truth in code.

**Harder:**
- No instant flag-flip rollback. Mitigated by calibration gate, additive migrations, watch-window protocol, and compute-then-apply split that enables dry-run validation.
- Calibration corpus must exist before Stage-1 merge. Mitigated by fixture-collection task in this plan.

## References

- Umbrella spec: `docs/superpowers/specs/2026-05-01-penalty-savings-program-design.md`
- Stage-1 plan: `docs/superpowers/plans/2026-05-01-penalty-savings-stage-1.md`
- Penalty fix spec (prerequisite): `docs/superpowers/specs/2026-04-29-penalty-fix-design.md`
