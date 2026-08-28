# Next campaign: Transactional Authority Consumption Adoption

## Campaign status

`BATCH_9_DELEGATE_MODEL_GOVERNANCE_AUTHORITIES_ADOPTED_BATCH_10_NOT_AUTHORIZED`

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
`docs/handoffs/transactional-authority-consumption-preparation-batch-0-complete.md`. Batch 1 was
subsequently authorized and completed as recorded below.

## Batch 1 result

Batch 1 defines the separately versioned `TransactionalAuthorityConsumptionContract` and
`AuthorityConsumptionRecoveryContract`, with the canonical design in
`docs/transactional-authority-consumption-contract.md`. No consumer was migrated and the contracts
perform no transition. The completion handoff is
`docs/handoffs/transactional-authority-consumption-batch-1-complete.md`.

Batch 2 was subsequently authorized and completed as recorded below.

## Batch 2 result

Batch 2 adopts the contracts in `OperationalCognitionInvocationClaimService` without changing the
claim schema or the authority→lease lock order. New claims seal the two unchanged authorities,
complete replay inputs, exact consumer/act, existing lock scopes, both consumptions, immutable
result, and complete pre-I/O recovery in one embedded envelope. The completion handoff is
`docs/handoffs/transactional-authority-consumption-batch-2-complete.md`.

## Batch 3 result

Batch 3 adds a test-only fault-injection seam to the adopted operational claim and proves all four
internal recovery observations: `PREPARED`, `CONSUMPTION_COMMITTED`, `RESULT_COMMITTED`, and
`COMPLETE`. Because both authority consumptions and the lifecycle result share one immutable write,
only `PREPARED` can leave no claim; every later injected failure observes the same complete sealed
claim. Exact retry and replay converge on that one result without credential resolution, provider
journal creation, provider invocation, network access, rollback, or authority unconsumption.

The completion handoff is
`docs/handoffs/transactional-authority-consumption-batch-3-complete.md`.

## Batch 4 result

Batch 4 adopts the transaction and recovery contracts in
`GovernanceCognitionInvocationClaimService`. New claims retain the existing claim schema and
deterministic claim ID while binding the exact normalized resolver output, complete request,
decision, lease and provider inputs, the governance authority→lease lock plan, both consumptions,
and the immutable pre-I/O result. Historical claims without the envelope remain exact replays.

Exact replay validates the complete fingerprint and envelope. Divergent transactional metadata
fails stopped, and a new two-process proof converges on one immutable claim. The existing
governance-lease interruption admission and enforcement paths retain the same competing lock order.

The completion handoff is
`docs/handoffs/transactional-authority-consumption-batch-4-complete.md`.

## Batch 5 result

Batch 5 adopts the contracts in `ProviderInvocationClaimService`. New Delegate claims preserve the
existing claim schema, deterministic identity, and single composite activation lock while sealing
the exact activation, turn authority, credential lease, logical authority order, both
consumptions, and immutable pre-I/O result. Historical claims remain exact replay without rewrite.

The two authority entries name the same existing composite lock scope because the service makes
one physical acquisition for the activation; this records which distinct authorities it protects
without inventing a second lock. Exact replay rejects divergent envelope metadata, and a
two-process proof converges on one transactional claim. Provider-journal creation, provider I/O,
unknown-outcome governance, and sealed-response recovery remain unchanged and outside the
transition.

The completion handoff is
`docs/handoffs/transactional-authority-consumption-batch-5-complete.md`.

## Batch 6 result

Batch 6 adopts the eight deterministic Delegate Senate consumers from Steps 19–42 through
`DelegateMissionSenateAuthorityTransition`. Each act retains its existing public API, authority,
jurisdiction, actor, source validation, result schema and ID, while acquiring one new shared scope
derived from that exact lifecycle authority before replay selection:

`delegate-senate-authority:<sha256 authorityId>`

The helper seals the complete lifecycle result surface and exact source digest into a
`ReplayFingerprint`, embeds the shared transaction/recovery envelope, and commits through
`ImmutableRecordStore`. Historical immutable results remain valid without rewrite. Adopted results
validate their envelope against the exact producing consumer and schema. Claim/claim contention and
a fault-after-commit proof converge on the same immutable result with no authority rollback or
external effect.

Five cognition-bearing consumers are deliberately not decorated: question authorship, testimony,
Senator finding, finding reconciliation, and final Senate disposition. Their Symfony AI call occurs
before the lifecycle result is sealed. A crash in that interval can repeat cognition, so an envelope
claiming `external_effect.started=false` would be false. They remain `RECOVERY_INCOMPLETE` pending a
separately prepared pre-I/O claim, journal, and unknown-outcome boundary.

The completion handoff is
`docs/handoffs/transactional-authority-consumption-batch-6-complete.md`.

## Batch 7 result

Batch 7 adopts the three model-bound Profile Senate opening consumers that already expose one
explicit single-use authority ID, one exact immutable source, one Lord Speaker actor, one existing
commit timestamp, and one immutable result: testimony opening, finding-authority opening, and
deliberation opening. They now share:

`profile-senate-authority:<sha256 authorityId>`

The exact authority lock encloses reread, validation, consumption, and one immutable result commit.
`ProfileSenateAuthorityTransition` seals the unchanged result surface in the shared transaction and
recovery envelope. Historical records remain valid without rewrite; envelope divergence fails
stopped; contention and fault-after-commit recovery converge on one result without external effect.

The boundary does not pretend the remainder is equivalent. Legacy deterministic Profile Senate
consumers do not expose a canonical authority ID or result-commit timestamp. Model-bound evidence
questioning writes a testimony turn and may separately derive panel readiness. Model-bound
disposition-authority opening lacks an existing commit timestamp and complete native closure field.
The two approval services likewise have no separately identified approval authority. Those paths
remain `RACE_EXPOSED` or `RECOVERY_INCOMPLETE` as recorded in the inventory. All question/finding,
reconciliation, and disposition cognition paths remain outside adoption because their model-call
outcome is not journaled before the lifecycle result.

The completion handoff is
`docs/handoffs/transactional-authority-consumption-batch-7-complete.md`.

## Batch 8 result

Batch 8 audits the full operational-adoption consumer cluster and adopts only its two truthful
single-result authorities: Seneschal reconciliation and final disposition. Both now share the
exact lock scope:

`operational-adoption-authority:<sha256 authorityId>`

The lock encloses source reread, competent-actor validation, existing-result selection, logical
consumption, replay fingerprinting and one immutable result commit. Historical records replay
without rewrite; an adopted envelope must reconstruct exactly; contention and fault-after-commit
recovery converge without external effect.

The apparent intake authority is `ABSENT`: presentation exposes only `intake_pending`, and intake
disposition records `evaluation_opening_authority=false`. Independent assessment authority is
`EXISTS_FRAGMENTED` but remains `RECOVERY_INCOMPLETE`: the assessment result and optional
all-assessments completion are separate writes, and a crash between them cannot reconstruct the
original completion timestamp. Neither missing authority identity nor a multi-write checkpoint is
invented.

The completion handoff is
`docs/handoffs/transactional-authority-consumption-batch-8-complete.md`.

## Batch 9 result

Batch 9 audits the Oracle/model-governance cluster and adopts exactly two Delegate Mission Curia
consumers with complete native transaction identity: model-criteria presentation and model-selection
decision. Both now share:

`delegate-model-governance-authority:<sha256 authorityId>`

The exact authority lock encloses source reread, actor validation, replay selection, logical
consumption and one immutable result commit. `DelegateMissionModelGovernanceAuthorityTransition`
fingerprints the unchanged result surface and embeds one complete version-1 envelope. Historical
results remain valid without rewrite; adopted envelope divergence fails stopped; contention and
fault-after-commit recovery converge without external effect.

The remainder is not forced into the boundary. Legacy Oracle acceptance, case-opening and
comparative-assessment powers are booleans without canonical single-use IDs. Eligibility finding
can separately close the phase; commission issuance writes both Curia and Oracle inbox records.
Legacy recommendation and planning-selection results omit native `instance_id`. Oracle research
crosses the sortie/external-evidence boundary. Delegate binding, access, resource and activation
consumers remain separately bounded construction, credential and provider-admission work.

The completion handoff is
`docs/handoffs/transactional-authority-consumption-batch-9-complete.md`.

Batch 10 remains unopened pending explicit authorization. Its smallest safe candidate is the
construction and admission consumer cluster.

## Provisional remaining-batch countdown

Four batches remain after Batch 9 under the current inventory. This is a planning forecast, not
authorization to combine consumers when proof exposes a narrower boundary:

1. Batch 10 — construction and admission consumers;
2. Batch 11 — older multi-write operational, bootstrap, Legate, Oracle/model-governance, and deferred operational-adoption recovery clusters;
3. Batch 12 — mechanical coverage reconstruction, explicit exclusions, and adversarial review; and
4. Batch 13 — documentation-only campaign closeout.

Any cluster that cannot share one lock, replay, recovery, and proof boundary must split rather than
be forced into this forecast.

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
