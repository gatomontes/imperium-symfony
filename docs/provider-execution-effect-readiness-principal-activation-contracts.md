# Provider Execution Effect Readiness — Batch 6 executor-principal activation contracts

## Result

`BATCH_6_AUTHORITY_EMPTY_EXECUTOR_PRINCIPAL_ACTIVATION_CONTRACTS_COMPLETE`

Two separately versioned v1 contracts define the future activation lifecycle
for the exact `ATTESTED_INERT` provider executor principal:

| Contract | Role | Not authority to |
| --- | --- | --- |
| `ProviderExecutorPrincipalActivationDecisionContract` | A future competent Imperator decision binding one exact attestation generation, admitted assurance record, scope, validity and single-use activation authority | Produce a decision, issue or consume authority, or activate anything |
| `ProviderExecutorPrincipalActivationContract` | A future immutable activation result binding the exact decision, consumed activation authority, assurance, boundary, attestation, principal generation, scope, validity and reconstruction posture | Produce an activation, activate a binding, issue execution authority or approach I/O |

No producer, validator, store or transition exists in Batch 6. Contract existence
grants no authority.

## Identity and competent authority

The existing `ProviderExecutorPrincipalContract` remains immutable at
`ATTESTED_INERT`. Activation is a separate record; it may not mutate the
attestation or silently advance its generation.

A future competent decision must bind the exact Imperator principal, office,
seat, binding and generation; the exact source authority; the exact principal
attestation; and the exact admitted provider-assurance record. Its permitted
transition is
`ACTIVATE_EXACT_ATTESTED_PROVIDER_EXECUTOR_PRINCIPAL_GENERATION`.

An authorized disposition may describe one exact, expiring, single-use,
non-continuing activation authority. A refused disposition may not be treated as
authority. Neither disposition produces the authority or activation.

## Scope, expiry and revocation

Both contracts bind provider, operation, execution boundary, principal identity,
principal generation, process boundary and same-process execution. Provider,
operation and generation substitution are prohibited.

The future activation record has only `ACTIVE`, `EXPIRED` and `REVOKED`
statuses. Validity requires effective time, expiry and a nullable revocation
reference. Expiry or lawful revocation before a later first-byte winner must
refuse that operation. No existing provider-execution authority, binding
activation or process identity can extend the validity window.

The provider binding remains `BOUND_INACTIVE`. Principal activation, if later
implemented, cannot activate or mutate it.

## Crash, contention and reconstruction shape

The contracts require a consumed activation-authority reference and one
immutable activation identity. They do not choose a lock, store, producer or
consume-to-commit algorithm.

Future reconstruction must be read only and exact-replay only. It may not
reactivate the principal or upgrade its generation. Competing decisions,
authorities or activations for one attestation generation remain unresolved
until a later validator and production proof defines the authoritative winner.

`UNKNOWN_REPLAY_PROHIBITED` remains binding after any possible provider effect
start. Principal reconstruction cannot authorize retry.

## Secret exclusion and threat model

Neither contract contains a credential reference, credential bytes,
environment-variable name or process-local capability identity. Exact identity,
scope, assurance and validity are durable facts; credential possession remains
stationary inside the future winning same-process boundary.

The declared ceiling remains `TRUSTED_WRITER_CANONICAL_INTEGRITY` on one
authoritative root. Contract shape does not prove hostile-writer
non-forgeability, distributed uniqueness, multi-host consensus, split-brain
resistance, remote provider authorship or provider conformance.

## Closed perimeter

Batch 6 defines constants only. It does not produce or validate a decision,
issue or consume activation authority, activate or reactivate a principal,
mutate an attestation, activate a provider binding, define a live-call runtime,
issue or consume execution authority, handle or resolve a credential or
capability, invoke a provider, perform external I/O, authorize retry, migrate a
live consumer or command, or open Iron Gate or Lazaretto.

## Batch 7 gate

Only Batch 7 may next be considered: pure fail-closed validators for the two
Batch 6 contracts and immutable caller-supplied offline fixture stores. Batch 7
may validate and store fixtures only.

Batch 7 may not produce a competent decision or activation authority, consume
authority, activate a principal or binding, define a live-call runtime, issue or
consume execution authority, handle credentials or capabilities, invoke a
provider, perform external I/O, authorize retry, migrate a consumer, or open
Iron Gate or Lazaretto.

Estimated campaign countdown after Batch 6: approximately four batches,
excluding any separately selected sterile provider-conformance campaign.
