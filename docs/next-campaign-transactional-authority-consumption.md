# Next campaign: Transactional Authority Consumption Adoption

## Campaign status

`PREPARATION_BATCH_0_COMPLETE_BATCH_1_NOT_AUTHORIZED`

Operational Cognition Lease Interruption is terminal through Batch 6. The next separately bounded
campaign is adoption of canonical transactional authority-consumption and recovery semantics across
the runtime. Selection authorizes Preparation Batch 0 only. It does not authorize migration of any
consumer merely because that consumer appears in the inventory.

## Why this boundary is next

Imperium already contains `AtomicTransition`, `ImmutableRecordStore`, `MutableStateStore`,
`ReplayFingerprint`, `RecordReferenceValidator`, and a narrow `AuthorityConsumptionStore` used by
Delegate mission-turn recovery. Recent cognition-lease interruption paths also prove native
single-winner locking and exact immutable results.

The substrate therefore exists, but adoption is fragmented. Many older authority consumers still
encode their own scan, validation, lock, replay, consumption, and result behavior. External effects,
generalized revocation, telemetry, containment, and incidents must not be built on an unproved mix
of transactional and lifecycle-specific semantics.

## Preparation Batch 0

Inventory every consumable runtime authority and every authority-like single-use lease or claim.
For each, identify:

1. schema, issuer, competent consumer, and exact scope;
2. authoritative source inputs and replay identity;
3. lock scope, lock order, and competing paths;
4. consumption representation and immutable result;
5. duplicate, conflicting, expired, stale, missing, superseded, and already-consumed behavior;
6. partial-write and process-death exposure;
7. recovery, retry, and unknown-outcome semantics;
8. current concurrency, tamper, and fault-injection tests;
9. whether the canonical persistence primitives are used; and
10. the smallest migration cluster that does not merge competent actors or authority types.

Classify every requirement as `EXISTS_CANONICALLY`, `EXISTS_FRAGMENTED`, `ABSENT`, or
`DEFERRED_BOUNDARY`. Additionally assign each inventoried consumer one mechanical posture:
`TRANSACTIONAL_CANONICAL`, `LOCKED_FRAGMENTED`, `RACE_EXPOSED`, `RECOVERY_INCOMPLETE`, or
`DEFERRED_EXTERNAL_BOUNDARY`.

The canonical output is
`docs/transactional-authority-consumption-preparation-inventory.md`.

Preparation Batch 0 completed that documentation-only inventory. It changed no runtime behavior.
The completion handoff is
`docs/handoffs/transactional-authority-consumption-preparation-batch-0-complete.md`. Batch 1 remains
unopened pending explicit authorization.

## Preparation stop conditions

Batch 0 may add or update inventory, analysis, campaign, handoff, and documentation-consistency
tests only. It may not:

- change an authority schema, issuer, consumer, scope, expiry, or competent actor;
- migrate a runtime consumer or alter replay behavior;
- create, consume, revoke, close, or propagate authority;
- change a lock scope or lock order;
- alter custody, deployment, cognition, provider-journal, or terminal-retirement behavior;
- open generalized revocation, telemetry, Curia reassessment, containment, or incidents;
- open Iron Gate execution, Lazaretto expansion, sorties, external execution receipts, or new
  credential-platform work; or
- create Delegate Mission Step 70, Runtime Integrity Hardening Step 36, Credential Boundary Batch
  18, Institutional Decision Integrity Batch 7, Continuous Governance Batch 17, or Operational
  Cognition Lease Interruption Batch 7.

## Provisional post-inventory sequence

No implementation step is authorized merely because it is listed:

1. define the exact shared transactional-consumption and recovery contract without replacing
   lifecycle-specific authority schemas;
2. adopt it in one narrow internal consumer with strong existing contention and reconstruction
   evidence;
3. prove one winner, complete replay equivalence, immutable-result uniqueness, and recovery at each
   commit boundary;
4. migrate the operational cognition authority/lease claim corridor if the inventory proves it is
   the safest representative high-consequence cluster;
5. migrate remaining internal consumers in separately authorized bounded clusters;
6. mechanically reconstruct adoption coverage and preserve explicit exclusions;
7. perform an adversarial review; and
8. close the campaign documentation-only.

## Completion criterion

Preparation is complete when a reviewer can identify, without searching unrelated services, every
consumable authority, its current transaction and recovery posture, every competing path, every
known partial-state exposure, and the exact first migration whose adoption reduces risk without
widening authority.

## Deferred successor

Production-grade Iron Gate execution authority and receipt binding is the intended next candidate
after this campaign, not an implied part of it. Its selection will require a separate preparation
and explicit authorization.
