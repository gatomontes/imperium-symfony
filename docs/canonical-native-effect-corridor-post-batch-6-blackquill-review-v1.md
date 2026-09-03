# Canonical Native Effect Corridor — post-Batch 6 Blackquill review v1

`CANONICAL_NATIVE_EFFECT_CORRIDOR_BATCH_6_CLOSURE_REQUALIFIED_CONTINUATION_AND_EXCLUSIVITY_UNPROVED`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

Reviewed baseline: merged `main` at
`2debf65c7a6534674f869b8703f66c9ee0c2f664` (PR #744).

## Judgment

Batches 1–6 produced useful pre-effect candidate machinery and did not perform
a live provider effect. Their current proof does not justify crossing into
Batch 7. The live-trial gate remains closed and is additionally suspended until
the defects below are remediated and independently audited.

## Material findings

### BQ-CNE-01 — uninterrupted same-process continuation is not enforced

`NativeEffectAtomicAdmissionService` seals a durable admission, but
`NativeEffectDoubleExecutionService::execute()` accepts only reconstructible
inputs: admission id, authority array, payload, idempotency key and time. It
does not require an unforgeable process-local continuation object consumed by
the winning admission call. A fresh process can therefore load the durable
facts and attempt first continuation while the admission is current and no
callback-start record exists.

The Batch 5 process test proves only that a fresh process cannot admit the same
authority again. It does not attempt continuation after `admit-and-exit`.

### BQ-CNE-02 — cross-authority effect-tuple exclusivity is absent

The effect replay identity includes `effect_authority_id` and
`effect_authority_digest`. Distinct authorities for the same operation,
destination, payload, provider, credential family, return contract and
idempotency commitment therefore produce distinct identities, admission ids
and locks. The implementation scans for reuse of one authority id; it does not
serialize a semantic effect tuple independently of authority identity.

The matrix promises a single winner for different authorities targeting the
same effect. The current construction cannot exercise that case.

### BQ-CNE-03 — continuation accepts caller-supplied authority semantics

Continuation compares the admission authority reference with
`NativeState::ref($authority, 'authority_id')`, which copies the supplied
id/schema/digest without recomputing the authority seal. Receipt binding then
copies `expected_return_contract` from that caller-supplied array. A modified
array retaining the original digest can therefore substitute receipt semantics
after admission.

### BQ-CNE-04 — recorded assertion total differs from CI

The Batch 6 ledger and handoff record 2,189 tests and 48,255 assertions. GitHub
Actions run 33813014897 at the reviewed commit completed successfully with
2,189 tests and 48,253 assertions. Both observations must be labeled by source;
neither count may be presented as the other.

## Preserved evidence

- The full GitHub PHPUnit job passed.
- No Batch 7 command exists and no live provider effect occurred.
- Atomic admission, callback-start-before-double, unknown-outcome refusal and
  secret-free candidate records remain useful substrate.
- Provider-double output remains synthetic evidence only.

## Controlling disposition

Batch 7 may not begin from the existing Batch 6 handoff, even if the exact
live-trial marker is later supplied. The corrective campaign must first prove
same-process continuation custody, authority-independent effect-tuple
exclusivity, immutable admission-derived receipt semantics and corrected
evidence accounting. A separate terminal Blackquill audit from merged corrected
`main` decides whether the suspended Batch 7 gate may be restored.
