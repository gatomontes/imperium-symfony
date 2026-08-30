# Provider Execution Boundary Redesign — Batch 1 contracts

## Result

`BATCH_1_CONTRACTS_COMPLETE_NO_IMPLEMENTATION`

Four separately versioned canonical contracts define the redesigned provider-execution surface:

| Contract | Durable role | Explicitly not |
| --- | --- | --- |
| `ProviderExecutionBoundaryContract` | Names the candidate same-process credential-owning boundary, stationary credential posture, admission ordering and declared threat model | A principal installation, authority, activation, credential operation or opened perimeter |
| `ProviderExecutorPrincipalContract` | Identifies the exact infrastructure principal, generation, process boundary, competence and validity required to execute | Execution authority, binding activation, credential access or I/O permission |
| `DurableProviderExecutionAuthorityContract` | Defines the future exact, expiring, single-use authority binding decision, boundary, principal, tool, effect, activation, provider, request, destination, assurance and validity | A produced authority, self-consumption, credential capability, effect start or retry authority |
| `SingleOperationProviderBindingActivationContract` | Defines one immutable activation for one exact operation, request, principal and boundary | Provider reselection, source-binding mutation, execution-authority issuance, credential resolution or I/O |

Contract existence grants no authority. No producer, issuer, attestor, activation transition, consumer,
validator, admission transition, credential boundary or reconstruction service is implemented in
this batch.

## Durable authority and process-local mechanism

`DurableProviderExecutionAuthorityContract` is the durable permission identity. It is designed to
survive process loss as an immutable, expiring and single-use artifact on the authoritative root.
It carries no credential reference, credential secret or serialized capability.

`CredentialCapability` remains an existing process-local enforcement mechanism. This batch neither
changes nor uses it. It is not referenced by any redesigned contract and may not be reinterpreted as
the durable authority. A future same-process executor may use a process-local enforcement object
only after an exact durable authority has been atomically consumed and effect-start has been
committed.

## Exact binding set

The durable authority schema requires references binding:

- the competent source decision;
- the credential-owning execution boundary;
- the exact executor-principal attestation;
- tool authority;
- effect authorization;
- one single-operation provider-binding activation;
- the immutable provider binding;
- exact request and commission identity;
- exact operation, destination, payload digest and request fingerprint;
- destination policy and assurance profile;
- provider, adapter and credential family;
- effective time, expiry and revocation reference; and
- single-use, non-continuing consumption state.

Any missing, changed, expired or revoked constituent must fail closed in a future implementation.
This batch implements no such validation or failure path.

## Required ordering

The boundary contract states the future ordering without performing it:

1. validate the intact boundary, current executor principal, exact activation and complete durable
   execution authority;
2. atomically consume the single-use execution authority and commit effect-start on the one
   authoritative root;
3. only the winner may resolve the stationary credential inside the same process;
4. only then may the provider adapter approach the first outbound byte; and
5. any crash after the durable winner preserves `UNKNOWN_REPLAY_PROHIBITED`.

The contract does not claim that current runtime behavior already satisfies this sequence.

## Legacy artifact posture

Existing `ProviderBindingActivationAuthorityContract`,
`SingleExecutionProviderBindingActivationContract`,
`AtomicProviderExecutionAdmissionContract`, opaque-capability custody/delivery contracts and their
services remain unchanged. They are historical runtime surfaces from the refused cross-process
custody route. They are neither migrated nor reinterpreted as the redesigned boundary, principal,
authority or activation.

Existing selection, claim, journal, admission, checkpoint, disposition, receipt and reconstruction
records remain evidence only. They grant no redesigned execution authority.

## Threat-model and secret boundary

The boundary contract fixes the claim to
`TRUSTED_WRITER_CANONICAL_INTEGRITY` and `SINGLE_AUTHORITATIVE_ROOT_ONLY`.
Hostile-writer non-forgeability, multi-host consensus and split-brain resistance remain unclaimed.
A local broker or external custodian remains a later boundary choice if the threat model expands.

Every redesigned contract excludes credential references, credential secrets and serialized
capability material. Contract records, logs, exceptions and reconstruction may not carry or recreate
credential material.

## Explicit non-authorities

These contracts do not define or activate a runtime principal or provider binding; issue or consume
authority; issue, transfer, persist, reconstruct, resolve or consume a credential capability; read a
credential; start an effect; invoke a provider; authorize retry; perform external I/O; migrate a live
command; open Iron Gate or Lazaretto; or grant continuing authority.

## Batch 2 gate

Only Batch 2 may next be considered: define the competent source-decision and issuance surfaces for
the exact execution boundary, exact executor-principal attestation, durable execution authority and
single-operation activation. Batch 2 may not install or activate a principal, produce an activation,
issue or consume execution authority, implement atomic admission, touch credentials, invoke a
provider, migrate a live command, or perform external I/O.
