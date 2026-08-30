# Provider Execution Boundary Redesign — Batch 2 decision and issuance surfaces

## Result

`BATCH_2_DECISION_ISSUANCE_CONTRACTS_VALIDATED_NO_PRODUCTION`

Batch 2 defines four separately versioned competent source-decision and issuance contract pairs:

| Future artifact | Decision/issuance contract | Permitted transition |
| --- | --- | --- |
| Provider execution boundary definition | `ProviderExecutionBoundaryDefinitionIssuanceContract` | `ISSUE_EXACT_PROVIDER_EXECUTION_BOUNDARY_DEFINITION` |
| Exact executor-principal attestation | `ProviderExecutorPrincipalAttestationIssuanceContract` | `ISSUE_EXACT_PROVIDER_EXECUTOR_PRINCIPAL_ATTESTATION` |
| Durable provider-execution authority | `DurableProviderExecutionAuthorityIssuanceContract` | `ISSUE_EXACT_DURABLE_PROVIDER_EXECUTION_AUTHORITY` |
| Single-operation provider-binding activation | `SingleOperationProviderBindingActivationIssuanceContract` | `ISSUE_EXACT_SINGLE_OPERATION_PROVIDER_BINDING_ACTIVATION` |

Each decision is separate from issuance. An `AUTHORIZED` decision may carry one exact, expiring,
single-use, non-continuing issuance authority. A `REFUSED` decision carries none. Contract
existence, validation and an authorized disposition do not produce the target artifact.

## Competence and exact basis

Every decision must bind:

- the competent source authority;
- an exact principal ID, office, seat, binding and generation;
- one target kind, ID, digest and schema;
- the proposed execution boundary and executor principal;
- provider binding, tool authority and effect authorization;
- exact request, destination policy and assurance profile;
- rationale, limitations, decision time and expiry; and
- an explicit statement that no external action was performed.

The shared `ProviderExecutionBoundaryRedesignIssuanceContractValidator` accepts only canonical,
sealed, digest-intact records with exact field order and shapes. It requires exact reference
structures, a current decision window no longer than fifteen minutes, the contract-specific target
kind and permitted transition, and single-use/non-continuing issuance posture.

An issuance must bind the exact source decision, the exact consumed issuance authority, exact issued
artifact reference and exact issuer principal. It must record that principal installation,
provider-binding activation, credential-capability issuance, credential resolution and external
action did not occur. The validator writes no record and consumes nothing.

## Authority separation

The four issuance routes are non-interchangeable. Authority to issue a boundary definition cannot
issue a principal attestation, durable execution authority or provider activation. The same
separation applies to every other route.

The decision and issuance contracts do not:

- define or install an execution boundary;
- install or activate an executor principal;
- issue or consume durable provider-execution authority;
- activate or mutate a provider binding;
- issue, transfer, persist, reconstruct, resolve or consume a credential capability;
- read a credential or secret;
- commit effect-start;
- invoke a provider or authorize retry;
- perform external I/O;
- migrate a live command; or
- open Iron Gate or Lazaretto.

## Legacy and threat-model posture

No existing cross-process custody, activation, claim, journal, admission, disposition, receipt or
reconstruction artifact is reinterpreted as one of these decisions or issuance authorities.
`CredentialCapability` remains process-local and is neither a target nor a durable authority.

The threat model remains `TRUSTED_WRITER_CANONICAL_INTEGRITY` on
`SINGLE_AUTHORITATIVE_ROOT_ONLY`. Canonical SHA-256 validation is not hostile-writer
non-forgeability, remote authorship, multi-host consensus or split-brain resistance.

## Batch 3 gate

Only Batch 3 may next be considered: implement exact immutable production for the provider-execution
boundary definition and executor-principal attestation routes. Those two routes may consume only
their own exact issuance authorities and must leave the boundary and principal inert.

Batch 3 may not issue durable provider-execution authority, produce provider-binding activation,
implement atomic execution admission, handle a credential or capability, resolve a secret, invoke a
provider, perform external I/O, migrate a live command, or open Iron Gate or Lazaretto.
