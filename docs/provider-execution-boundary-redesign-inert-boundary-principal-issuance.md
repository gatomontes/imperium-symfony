# Provider Execution Boundary Redesign — Batch 3 inert boundary and principal issuance

## Result

`BATCH_3_INERT_BOUNDARY_AND_PRINCIPAL_ATTESTATION_PRODUCTION_COMPLETE`

Batch 3 implements one shared, same-root issuance service for exactly two Batch 2 routes:

1. immutable provider-execution boundary definition; and
2. immutable executor-principal attestation.

Both routes require a pre-existing, intact, validator-approved `AUTHORIZED` decision. The service
consumes only that decision's exact, expiring, single-use issuance authority through
`AuthorityConsumptionStore`, seals the exact target artifact, and seals one issuance record. It
does not produce source decisions or issuance authority.

## Boundary definition

The boundary is sealed at `DEFINED_INERT` and requires:

- `SAME_PROCESS_GOVERNED_EXECUTOR`;
- stationary deployment-owned credential possession;
- no cross-process capability transfer;
- no credential-reference, secret or credential reconstruction persistence;
- exact-authority consumption and effect-start commitment before credential resolution;
- effect-start commitment before the first outbound byte;
- credential resolution only inside the winning same process; and
- `TRUSTED_WRITER_CANONICAL_INTEGRITY` on
  `SINGLE_AUTHORITATIVE_ROOT_ONLY`, without hostile-writer, multi-host or split-brain claims.

The record is a boundary definition, not an active execution perimeter.

## Executor-principal attestation

The attestation is sealed at `ATTESTED_INERT`. It binds one exact boundary, principal ID,
infrastructure role, binding ID, generation, process-boundary ID, operation, provider, adapter,
credential family, same-process requirement, validity and null revocation reference.

Attestation does not install or activate the principal. It creates no process, service identity,
credential access, provider authority or execution permission.

## Atomicity, replay and interruption

Each route is serialized under an exact issuance-authority scope on the single authoritative root.
The issuance authority is durably consumed before the target artifact and issuance record are
committed. If interruption occurs after consumption, exact replay by the same consumer converges on
the same consumption and can finish the immutable artifact and issuance records. Changed replay
conflicts with the precommitted target digest or immutable record.

This is same-root recovery only. It claims no distributed transaction, hostile-writer
non-forgeability, multi-host consensus or split-brain resistance.

## Closed effects

Every issuance record proves:

- `principal_installed: false`;
- `provider_binding_activated: false`;
- `credential_capability_issued: false`;
- `credential_resolved: false`; and
- `external_action_performed: false`.

The implementation imports no credential broker, credential capability, provider transport,
AgentMail adapter, Iron Gate or Lazaretto component. It issues no durable provider-execution
authority and produces no single-operation provider-binding activation.

## Batch 4 gate

Only Batch 4 may next be considered: immutable production of one exact single-operation
provider-binding activation against the intact inert boundary, current inert executor-principal
attestation, inactive provider binding and its own exact issuance authority.

The activation must remain `ACTIVATED_UNCONSUMED`. Batch 4 may not issue or consume durable
provider-execution authority, implement atomic execution admission, resolve or handle credentials,
commit effect-start, invoke a provider, authorize retry, perform external I/O, migrate a live
command, or open Iron Gate or Lazaretto.
