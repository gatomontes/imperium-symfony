# Provider Execution Effect Readiness — Batch 7 principal-activation validation

## Result

`BATCH_7_FAIL_CLOSED_PRINCIPAL_ACTIVATION_FIXTURE_VALIDATION_COMPLETE`

Batch 7 adds one pure validator and one immutable store for caller-supplied
offline fixtures implementing the two Batch 6 contract shapes. It creates no
competent decision, activation authority or principal activation.

## Decision validation

A decision fixture is accepted only when it is canonical, sealed and digest
intact and binds:

- one exact `ATTESTED_INERT` principal attestation and generation;
- its exact `DEFINED_INERT` same-process execution boundary;
- one current `EVIDENCE_ADMITTED_NO_EXECUTION_AUTHORITY` assurance record;
- exact provider, operation, principal, generation and process-boundary scope;
- an exact competent actor and source-authority reference;
- a current validity window bounded by both attestation expiry and assurance
  review due time; and
- for `AUTHORIZED` only, one unconsumed, expiring, single-use,
  non-continuing activation-authority shape bound to the attestation digest.

A `REFUSED` decision carries no activation authority. Contract validation
cannot convert either disposition into authority.

## Activation validation

An activation fixture is accepted only when it binds the exact authorized
decision, exact consumed activation authority, exact assurance, boundary,
attestation and unchanged principal generation. Provider, operation and
generation substitution are false.

The consumed-authority evidence must name the decision's authority, decision
digest and schema, record exact consumption time, and remain non-continuing.
Reconstruction must be read only, exact-replay only, non-reactivating and
generation preserving.

`ACTIVE` requires a current window and no revocation reference. `EXPIRED`
requires the expiry to have passed. `REVOKED` requires an explicit reference.
Validation never performs any lifecycle transition.

## Fixture store

`ProviderExecutorPrincipalActivationFixtureStore` writes only caller-supplied
validated fixtures under:

- `var/imperium/evidence/provider-execution-effect-readiness/principal-activation-decisions`; and
- `var/imperium/evidence/provider-execution-effect-readiness/principal-activations`.

Exact replay converges through `ImmutableRecordStore`; changed content under
one identity conflicts. The store does not read a live principal, activate a
process, consume authority or repair evidence.

## Secret exclusion and threat ceiling

Exact field order excludes credential references, credential bytes,
environment-variable names and process-local capability identity. The validator
and store import no credential broker, capability, provider transport,
execution-authority issuer or authority-consumption store.

The ceiling remains one-root `TRUSTED_WRITER_CANONICAL_INTEGRITY`.
Validation does not prove hostile-writer non-forgeability, distributed
uniqueness, split-brain resistance, provider authorship or provider
conformance. `UNKNOWN_REPLAY_PROHIBITED` remains binding after any possible
provider effect.

## Closed perimeter

Batch 7 does not produce a competent decision, issue or consume activation
authority, activate or reactivate a principal, mutate its attestation, activate
a provider binding, define a live-call runtime, issue or consume execution
authority, handle or resolve a credential or capability, invoke a provider,
perform external I/O, authorize retry, migrate a live consumer or command, or
open Iron Gate or Lazaretto.

The principal remains inert and the provider binding remains inactive.

## Batch 8 gate

Only Batch 8 may next be considered: offline interruption, exact replay,
changed-evidence conflict and same-root contention proof for both fixture paths.
Batch 8 may not introduce a producer or runtime transition.

Estimated campaign countdown after Batch 7: approximately three batches,
excluding any separately selected sterile provider-conformance campaign.
