# Provider Execution Boundary Redesign — Batch 4 single-operation activation

## Result

`BATCH_4_SINGLE_OPERATION_ACTIVATION_ISSUED_UNCONSUMED`

Batch 4 implements immutable production of one exact
`SingleOperationProviderBindingActivationContract` artifact at
`ACTIVATED_UNCONSUMED`.

The route requires a pre-existing, intact, validator-approved `AUTHORIZED` activation decision and
atomically consumes only that decision's exact, expiring, single-use issuance authority. It does not
issue or consume durable provider-execution authority.

## Exact activation lineage

The activation binds:

- one intact `DEFINED_INERT` same-process execution boundary;
- one intact, current and unrevoked `ATTESTED_INERT` executor-principal attestation;
- one intact and current `BOUND_INACTIVE` provider implementation binding;
- the binding's exact governed tool authority;
- the binding's exact effect-authorization target;
- one exact request reference and request ID;
- operation, destination, payload digest and request fingerprint;
- the binding's exact destination policy and assurance profile;
- provider and adapter shared by the binding and executor competence;
- the binding's exact credential family;
- an execution identity with provider and request substitution prohibited; and
- the earliest expiry of the decision, executor attestation and provider binding.

The decision precommits the canonical activation-candidate digest. The sealed activation then adds
the exact decision and issuance-authority lineage without creating a cyclic digest dependency.

## Activation is not execution

The source provider binding remains `BOUND_INACTIVE`. The activation artifact means only that this
exact binding is selected for this exact single operation and may later be referenced by a separately
issued durable execution authority. It does not mutate the binding or make the provider callable.

The activation record seals:

- its exact consumed activation-issuance authority;
- `status: ACTIVATED_UNCONSUMED`;
- `single_operation: true`; and
- `continuing_authority: false`.

The issuance record proves:

- `binding_activation_issued: true`;
- `principal_installed: false`;
- `provider_binding_activated: false`;
- `credential_capability_issued: false`;
- `credential_resolved: false`; and
- `external_action_performed: false`.

Here, `provider_binding_activated: false` means the immutable source binding was not mutated to
`BOUND_ACTIVE`. The separate single-operation activation artifact is the only produced target.

## Replay, contention and expiry

The issuance-authority scope is serialized on the single authoritative root. Exact replay converges
on the same authority consumption, activation and issuance records. Changed request content changes
the candidate digest and refuses before authority consumption or record commitment. Expired
decision, principal attestation or provider binding refuses before activation.

These are same-root guarantees only. No hostile-writer, multi-host, distributed transaction or
split-brain guarantee is claimed.

## Closed effects

The implementation imports no durable provider-execution authority, credential broker, credential
capability, provider transport, AgentMail adapter, Iron Gate or Lazaretto component. It does not
resolve credentials, commit effect-start, invoke a provider, authorize retry, migrate a command or
perform external I/O.

## Batch 5 gate

Only Batch 5 may next be considered: immutable issuance of one exact, expiring, single-use durable
provider-execution authority binding the intact boundary, current executor attestation,
`ACTIVATED_UNCONSUMED` activation, inactive provider binding, exact tool/effect/request/destination
and assurance tuple.

The authority must remain issued and unconsumed. Batch 5 may not consume that authority, implement
atomic execution admission, resolve or handle credentials, commit effect-start, invoke a provider,
authorize retry, perform external I/O, migrate a live command, or open Iron Gate or Lazaretto.
