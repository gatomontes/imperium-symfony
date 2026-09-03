# Next campaign: Native Inspection Snapshot Consistency

`NATIVE_INSPECTION_SNAPSHOT_CONSISTENCY_CAMPAIGN_SELECTED`
`CANONICAL_CONSUMER_INTEGRATION_TERMINAL_AUDIT_ACCEPTED_BOUNDED_PRE_EFFECT`
`NONAUTHORIZING_INSPECTION_CONSISTENCY_RESIDUAL_OPEN`

## Why this campaign exists

The canonical-consumer correction proved that the established journal-bound
consumer enters the native outer exclusion scope before admission, credential
consumption and callback execution. Its authorizing pre-effect cut therefore
remains accepted.

The post-campaign Blackquill review found a narrower residual:
`NativeBindingReader::interpret()` performs read-only inspection without holding
the native transition lock. Claim and binding snapshots detect changes to those
sources, but the inspection reads journals, transition commits and reconstructed
lifecycle evidence across multiple operations. A concurrent publication could
therefore expose a transient or stale classification without changing the
snapshotted claim or binding sources.

The current result is explicitly non-authorizing, so this is not an execution
bypass and does not reopen the completed canonical-consumer campaign. It is an
unanswered consistency question. This campaign must define and prove the exact
observation guarantee before inspection output is relied upon by operators,
auditors or later orchestration.

## Governing question

Can read-only native inspection return a coherent, reproducible classification
during concurrent transition publication and interruption without becoming an
authority, effect gate, retry grant or second transition protocol?

## Required classifications

Preparation Batch 0 must classify each inspected source and race as:

- `EXISTS_CANONICALLY`
- `EXISTS_FRAGMENTED`
- `ABSENT`
- `DEFERRED_BOUNDARY`

At minimum inventory:

1. every caller of `interpret`, `forClaim`, `forJournal`, `read` and
   `NativeReconstructor::reconstruct`;
2. which callers already hold `native-provider-transition` and which do not;
3. every filesystem source read while deriving BOUND_INACTIVE,
   COMMITTED_CURRENT, COMMITTED_NOT_CURRENT, INCOMPLETE, CORRUPT and
   UNRELATED_OPERATION;
4. the mutation points and ordering of journal, source, successor, authority,
   activation, revocation, transition and migration publication;
5. races between inspection and publication, revocation, expiry, migration,
   interruption, replacement and deletion;
6. whether the existing native lock can safely cover inspection without lock
   inversion, mutation, observable lock-file side effects or liveness damage;
7. whether an optimistic whole-read-set snapshot can prove coherence without
   becoming a parallel commit protocol;
8. whether current classifications promise linearizability, snapshot
   consistency, monotonicity or only conservative best-effort observation;
9. time-of-check/time-of-use risks if inspection output is later cached,
   displayed, signed, admitted or passed to another process;
10. the smallest adversarial separate-process proof matrix and exact
    non-authorizing result shape;
11. Windows/POSIX and single-host filesystem assumptions;
12. documentary claims and tests that currently overstate the inspection
    guarantee.

## Planned sequence

- Preparation Batch 0: inventory callers, read sets, write order, lock graph,
  race matrix, semantic promise and evidence gaps. Documentation only.
- Batch 1: define one canonical non-authorizing inspection-result contract and
  consistency guarantee.
- Batch 2: implement the smallest safe coherence boundary selected by the
  inventory; do not add provider authority or retry.
- Batch 3: prove separate-process publication, revocation, expiry,
  interruption and repeated-read behavior.
- Batch 4: prove container/CLI wiring, zero authorization transfer, zero
  credential access and zero provider effect.
- Batch 5: conduct a separate terminal Blackquill audit from clean merged
  Batch 4 `main`.

Five implementation/audit stages follow Preparation Batch 0. Their shape may be
reduced by the inventory, but no later stage may be silently collapsed into
Preparation Batch 0.

## Preparation Batch 0 authorization

Authorized now:

- read the required sources;
- search callers and lock acquisition sites;
- produce a versioned inventory, race matrix and proposed smallest sequence;
- update documentary status and tests that assert only the preparation
  artifacts.

Not authorized now:

- runtime or service-container changes;
- acquiring a new lock in production code;
- changing inspection classifications or result schemas;
- executing a mission, provider, credential, capability or external I/O;
- publishing native state outside disposable tests;
- opening Iron Gate or Lazaretto;
- granting execution, retry, continuing or recovery authority;
- removing `BOUND_INACTIVE`, historical v3 `NOT_IMPLEMENTED`,
  `UNKNOWN_REPLAY_PROHIBITED`, or the bounded pre-effect qualification;
- claiming linearizable or coherent inspection before executable proof.

## Exit criterion

The campaign may close only when the chosen inspection contract is enforced at
every applicable caller and adversarial separate-process evidence proves the
claimed consistency level without enabling an effect or transferring authority.

## Current status

Preparation Batch 0 is complete at
`NATIVE_INSPECTION_SNAPSHOT_CONSISTENCY_PREPARATION_BATCH_0_COMPLETE`.
The inventory selected optimistic whole-read-set snapshot consistency with a
bounded conservative refusal; no runtime implementation is authorized by that
selection. The continuation handoff is
`docs/handoffs/native-inspection-snapshot-consistency-preparation-batch-0-complete.md`.
Preparation Batch 0 and Batch 1 are complete. The canonical contract is
`docs/native-inspection-snapshot-consistency-contract-v1.md`; it selects equal
before/after whole-read-set manifests with at most two attempts and preserves all
existing public result projections. The continuation handoff is
`docs/handoffs/native-inspection-snapshot-consistency-batch-1-complete.md`.
Batch 2 is complete. `NativeInspectionSnapshot` implements the shared bounded
read-only observation boundary without a production lock or public result
change. The continuation handoff is
`docs/handoffs/native-inspection-snapshot-consistency-batch-2-complete.md`.
Three stages remain.
