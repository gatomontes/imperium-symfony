# Next campaign — Canonical Native Effect Reconciliation Shared-Exclusion Remediation

`CANONICAL_NATIVE_EFFECT_RECONCILIATION_SHARED_EXCLUSION_REMEDIATION_SELECTED`
`QUARANTINED_CANDIDATE_NOT_ACCEPTED`
`CANONICAL_NATIVE_EFFECT_RECONCILIATION_SHARED_EXCLUSION_REMEDIATION_COMPLETE_LOCAL`
`SHARED_EXCLUSION_ACCEPTED_BOUNDED_SINGLE_HOST_NO_LIVE_EFFECT`
`HISTORICAL_PREPARATION_GATE_PRODUCTION_CORRECTION_NOT_AUTHORIZED`
`REMOTE_PUBLICATION_NOT_AUTHORIZED`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

## Purpose

Close the decision-publication and at-use time-of-check/time-of-use races by
proving that currentness validation, exact capability consumption and durable
publication share the same exclusion as every independently mutable
Operator-Root, native-principal, source-generation and lifecycle writer.

Original campaign countdown: six stages including Preparation Batch 0.
Current campaign countdown: zero local stages remain.

## Governing finding

The controlling review is `docs/canonical-native-effect-reconciliation-shared-exclusion-post-publication-blackquill-review-v1.md`.

The quarantined range
`afcaf025d097db0b9adddac25a9083a8be2322a0..7d3b8818717fe74fb822a195689d8b3c51030862`
was locally authorized but remotely published without authority. It also leaves
decision publication and both at-use cuts on lock domains that do not exclude
native-state mutation. It is evidence of failure only, not accepted
implementation or proof.

## Stage sequence

### Preparation Batch 0 — lock and interleaving inventory

Inventory every lock identity, acquisition order, protected store, mutable
writer, validation cut, consumption cut and publication cut from decision
authorization through issuance and claim derivation. Build deterministic
authority-empty race harnesses that reproduce:

- DP01: current preview -> native/source mutation -> stale decision publication;
- IU01: issuance currentness pass -> native/source mutation -> capability
  consumption and reconciliation-authority publication; and
- CU01: claim currentness pass -> native/source mutation -> capability
  consumption and claim publication.

Classify every edge as `SHARED_EXCLUSION_PROVED`,
`DISJOINT_LOCK_RACE_REPRODUCED`, `ORDERING_HAZARD`,
`EXISTS_SEQUENTIAL_ONLY` or `DEFERRED_BOUNDARY`. Documents, harnesses and
tests only; no production correction.

### Batch 1 — canonical shared-exclusion and lock-order contract

Define the one native-state exclusion contract, permitted nesting/ordering,
semantic-target serialization inside that exclusion, retry law, interruption
cuts and refusal vocabulary. Prove that no disjoint target lock may substitute
for mutation exclusion.

### Batch 2 — decision publication correction

Move current source resolution, decision construction and decision/issuance-
authority publication into the shared native-state exclusion. Prove revocation,
generation and lifecycle mutation cannot interleave between validation and
publication.

### Batch 3 — issuance and claim at-use correction

Bring issuance-use and claim-use currentness, exact typed-capability
consumption and durable publication under the shared native-state exclusion
while preserving target-wide winner semantics and avoiding nested-lock
deadlock.

### Batch 4 — adversarial concurrency, interruption and platform proof

Run deterministic competing mutation/use processes, interruption at every
durable cut, exact retries, changed-input conflicts, Windows/Linux lock identity,
fresh-process custody and frozen-perimeter tests. Distinguish cooperative
single-host proof from distributed and hostile-writer exclusions.

### Batch 5 — separately sequenced terminal audit

Begin only from clean merged Batch 4 `main`. Independently reconstruct every
lock and durable edge, rerun deterministic races plus focused/full PHPUnit,
retain exact-SHA GitHub CI and decide bounded closure. Batch 7 remains
suspended.

## Current authorization

The Operator subsequently authorized uninterrupted local campaign completion.
All six local stages are complete from the separately merged Batch 4 base.
No provider, credential, network/external I/O, mission, email, Iron Gate,
Lazaretto, live trial, remote publication or Batch 7 action followed. GitHub CI
is not claimed. The distributed and hostile-writer boundaries remain deferred.
