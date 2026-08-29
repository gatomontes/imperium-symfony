# Provider Binding Activation and Capability Custody Batch 3 complete

## Result

Batch 3 implements the immutable single-execution activation transition. La Cortine consumes the
exact Batch 2 activation authority and seals one `ACTIVATED_UNCONSUMED` activation lease after
revalidating the exact pre-I/O claim and inactive binding. Replay, contention, expiry and lineage
tamper fail closed or converge exactly. The source binding is not mutated.

Provider Execution Assurance remains paused.

## Authorized continuation

Only Batch 4 is authorized: implement truthful opaque capability custody for one exact
already-issued capability and its one-time delivery route across processes. It must preserve capability
identity without persisting or reconstructing credential references, secrets, serialized capability
material or provider authentication material. Refusal is required if the environment-backed broker
cannot prove the same already-issued capability across processes.

Batch 4 may not issue a credential capability, consume the activation lease, implement atomic execution admission,
resolve credentials, migrate the command, invoke a provider, perform external I/O, open Iron Gate
or Lazaretto, or change inbound webhook, sortie, credential-platform,
revocation, propagation, telemetry, reassessment, containment or incident behavior.
