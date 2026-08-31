# Provider Effect Principal and Binding Activation Resumption Batch 2 validation

## Result

RESUMPTION_BATCH_2_PURE_VALIDATORS_AND_SEGREGATED_IMMUTABLE_FIXTURE_STORES_COMPLETE

Batch 2 adds pure validation and segregated immutable storage for caller-supplied
offline fixtures only.

## Proved boundary

The canonical resolution-admission validator fails closed unless:

- every record has the exact schema, ordered field set, sealed canonical digest
  and exact reference identity;
- the production winner names the exact sealed activation decision and records
  no principal activation, binding activation, authority consumption, credential
  or capability handling, provider invocation, external action or continuing
  authority;
- the activation target exactly matches the decision actor and scope;
- the activation authority is unconsumed, exercisable, single-use, effective,
  unexpired and unrevoked;
- the replay/contention root exactly binds the instance, principal generation,
  process boundary, production, decision and authority; and
- recursive secret exclusion rejects credential-, capability-, secret-, token-,
  API-key- or environment-variable-shaped material.

The activation-input validator additionally requires byte-for-structure
agreement with the admitted evidence, target, authority and replay root. Exact
replay succeeds; changed-evidence conflict refuses.

Resolution admissions and activation inputs are written to separate immutable
offline fixture namespaces. An exact duplicate is idempotent. A same-identity
changed record is same-root contention and refuses as immutable tampering.

## Non-authority posture

The fixtures are supplied by the caller. There is no live custody, resolution,
production, authority issuance or consumption, principal or provider-binding
activation, credential or capability handling, provider invocation, retry,
external I/O or live migration. Iron Gate and Lazaretto remain closed.

The provider binding remains `BOUND_INACTIVE`.
`UNKNOWN_REPLAY_PROHIBITED` remains binding.
