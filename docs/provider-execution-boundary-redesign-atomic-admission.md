# Provider Execution Boundary Redesign — Batch 6 atomic execution admission

## Result

`BATCH_6_ATOMIC_AUTHORITY_CONSUMPTION_EFFECT_START_COMPLETE`

Batch 6 defines and implements the redesigned
`GovernedProviderExecutionAdmissionContract`.

The admission record is the single immutable winner fact for both:

1. consumption of one exact durable provider-execution authority; and
2. commitment of local effect-start truth before credential resolution and before the first
   outbound byte.

Those facts are not split across separate consumption and journal files.

## Exact admission lineage

A first admission validates:

- one intact, current, unrevoked, single-use, exercisable and unconsumed durable authority;
- the exact `DEFINED_INERT` same-process execution boundary;
- the exact current `ATTESTED_INERT` executor-principal attestation;
- the exact current `ACTIVATED_UNCONSUMED` single-operation activation;
- the unchanged current `BOUND_INACTIVE` provider implementation binding;
- instance, boundary, principal, activation, binding, tool, effect, request, destination, assurance,
  execution, provider, adapter and credential-family equality; and
- the boundary's mandatory authority-consumption/effect-start/pre-resolution/pre-I/O ordering.

The admission is serialized under the durable authority ID on the single authoritative root.

## One winner record

The immutable admission contains:

- `authority_consumption.consumed: true`;
- `authority_consumption.single_use: true`;
- `authority_consumption.continuing_authority: false`;
- one authority-specific winner scope;
- `LOCAL_EFFECT_START_COMMITTED_PRE_RESOLUTION_PRE_IO`;
- `local_effect_start_committed: true`;
- `credential_resolution_permitted_after_checkpoint: true`;
- `credential_resolved: false`;
- `external_io_started: false`;
- `provider_invoked: false`;
- `automatic_replay_permitted: false`;
- `exact_admission_continuation_permitted: true`; and
- `outcome: NOT_ATTEMPTED`.

The status is `ADMITTED_EFFECT_START_PRE_RESOLUTION_PRE_IO`.

## Replay, interruption and expiry

Exact concurrent callers serialize to one admission file. Once that record exists, exact replay
returns the same winner rather than creating another consumption or effect-start fact.

If a process exits after the atomic file rename, reconstruction may continue only from that exact
admission. The original authority cannot create another winner. Exact admission reconstruction may
still return the existing winner after constituent expiry; this is evidence/continuation identity,
not a new authorization. If no winner exists, expired or revoked authority refuses first admission.

Because no provider operation has begun, the checkpoint records `NOT_ATTEMPTED`, not provider
success or failure. Automatic replay remains prohibited so continuation cannot silently become a
second execution.

## Why the legacy admission is not reused

The older `AtomicProviderExecutionAdmissionContract` binds cross-process capability custody and
delivery from the refused architecture. The redesigned admission binds stationary credential
possession and durable execution authority. The old contract is unchanged and is not reinterpreted,
migrated or used here.

## Closed effects and threat model

The implementation imports no `AuthorityConsumptionStore`, credential broker, credential
capability, provider transport, AgentMail adapter, Iron Gate or Lazaretto component. No credential
is resolved, no capability issued or reconstructed, no provider invoked and no external I/O
performed.

The guarantee remains one-root `TRUSTED_WRITER_CANONICAL_INTEGRITY`. It does not claim
hostile-writer non-forgeability, multi-host consensus, distributed atomicity or split-brain
resistance.

## Batch 7 gate

Only Batch 7 may next be considered: same-process stationary credential resolution from one exact
admission winner. Resolution must occur inside the credential-owning boundary, expose
authentication only to a callback-local non-provider proof, persist no credential reference or
secret, and record no provider invocation or external I/O.

Batch 7 may not invoke a provider, send an outbound byte, authorize retry, migrate a live command,
open Iron Gate or Lazaretto, or claim provider outcome.
