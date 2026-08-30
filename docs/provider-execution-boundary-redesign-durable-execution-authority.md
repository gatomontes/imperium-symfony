# Provider Execution Boundary Redesign — Batch 5 durable execution authority

## Result

`BATCH_5_DURABLE_EXECUTION_AUTHORITY_ISSUED_UNCONSUMED`

Batch 5 implements immutable issuance of one exact
`DurableProviderExecutionAuthorityContract` artifact. The artifact is expiring, single-use,
non-continuing, exercisable and unconsumed.

The route requires a pre-existing, intact, validator-approved `AUTHORIZED` decision and atomically
consumes only that decision's issuance authority. Issuing the execution authority does not consume
the execution authority itself.

## Exact authority lineage

The authority binds:

- one intact `DEFINED_INERT` same-process execution boundary;
- one intact, current and unrevoked `ATTESTED_INERT` executor-principal attestation;
- one intact, current `ACTIVATED_UNCONSUMED` single-operation activation;
- the unchanged `BOUND_INACTIVE` provider implementation binding;
- exact tool and effect authorization;
- exact request and commission identity;
- exact operation and destination;
- payload digest and request fingerprint;
- destination policy and assurance profile;
- execution identity, provider, adapter and credential family;
- explicit prohibition of provider, payload and destination substitution; and
- a current validity window no later than the decision, principal, activation or binding expiry,
  with null revocation.

The Batch 2 durable-authority decision contract now includes
`provider_binding_activation` in its exact basis. This closes the previously omitted lineage edge;
no other decision or issuance route may substitute for it.

## Durable authority is not execution

The sealed authority states:

- `authority_single_use: true`;
- `authority_exercisable: true`;
- `consumed: false`; and
- `continuing_authority: false`.

The issuance record proves:

- `execution_authority_issued: true`;
- `principal_installed: false`;
- `provider_binding_activated: false`;
- `credential_capability_issued: false`;
- `credential_resolved: false`; and
- `external_action_performed: false`.

No execution-admission record, effect-start record or authority-consumption winner is created for the
durable execution authority.

## Replay, contention, expiry and revocation

The issuance-authority scope is serialized on the one authoritative root. Exact replay converges on
the same issuance-authority consumption, durable execution authority and issuance record. A changed
candidate changes the precommitted candidate digest or fails exact activation lineage before
issuance.

Expired decisions, principal attestations, activations or bindings fail closed. The authority's
expiry cannot exceed any constituent expiry. A non-null revocation reference is refused.

These are `TRUSTED_WRITER_CANONICAL_INTEGRITY` and
`SINGLE_AUTHORITATIVE_ROOT_ONLY` guarantees. They do not establish hostile-writer
non-forgeability, distributed transactions, multi-host consensus or split-brain resistance.

## Secret exclusion and closed effects

The authority and issuance records carry no credential reference, secret or capability material.
The implementation imports no credential broker, capability, provider transport, AgentMail adapter,
Iron Gate, Lazaretto or effect-start service. It does not resolve credentials, invoke a provider,
authorize retry, migrate a command or perform external I/O.

## Batch 6 gate

Only Batch 6 may next be considered: define and implement the redesigned same-root atomic execution
admission that validates the exact boundary, executor, activation, binding and durable authority;
atomically consumes that durable authority; and commits local effect-start truth before credential
resolution or the first outbound byte.

Batch 6 may not resolve a credential, issue or reconstruct a capability, invoke a provider,
authorize retry, perform external I/O, migrate a live command, or open Iron Gate or Lazaretto.
