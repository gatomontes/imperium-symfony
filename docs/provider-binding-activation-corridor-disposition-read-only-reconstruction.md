# Provider Binding Activation Corridor Disposition read-only reconstruction

## Status

`BATCH_3_READ_ONLY_RECONSTRUCTION_AND_REFUSAL_CLASSIFICATION_COMPLETE`

`ActivationCorridorDispositionReadOnlyReconstructionService` reconstructs one exact candidate basis
from an intact target, one canonical runtime-principal version and caller-supplied immutable evidence.
It returns an ephemeral `ActivationCorridorDispositionReconstructionResultContract` result and
writes no record.

## Classification order

1. `REFUSED` when the canonical principal is absent, not effectively `ACTIVE`, instance-mismatched,
   lacks `corridor_disposition_authority`, retains credential-bearing material, or when the terminal
   custody refusal/activation lineage does not match exactly.
2. `INCOMPLETE` when any required evidence family is absent.
3. `CONFLICTED` when a supplied record is altered, schema-divergent, instance-divergent,
   semantically incompatible or does not supply the exact interruption/artifact coverage.
4. `ELIGIBLE` only when every exact record, lineage, refusal and principal requirement agrees.

Classification is not disposition. `ELIGIBLE` means only that one immutable evidence basis is
structurally complete for later authority consideration.

## Exact evidence coverage

Reconstruction requires:

- an authorized activation decision with no external action;
- one activation-authority issuance that did not activate a binding or issue a credential capability;
- one exact activation lease;
- all six unique activation decision/issuance interruption records with
  `CONVERGENT_RECOVERABLE` classification;
- at least one exact `QUARANTINED_EXPIRED_UNUSED` stranded-artifact disposition without source
  mutation or successor authority;
- the exact cross-process custody assessment at
  `REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE`;
- process-loss evidence at `POSSESSION_LOST` without reconstruction, credential resolution or
  external action; and
- credential/reference exclusion evidence at `EXCLUDED` without reference or secret observation.

Every record must be digest-intact and instance-consistent. The custody assessment and process-loss
evidence must bind the exact activation, and the target's terminal refusal must bind the exact
custody assessment.

## Principal truth

The service uses canonical lifecycle reconstruction from the runtime principal store; a supplied
principal fixture is not accepted as a substitute. Missing, inactive, expired, suspended, revoked,
superseded or retired principal evidence refuses before evidence completeness is considered.

An offline test fixture can demonstrate the `ELIGIBLE` branch, but that does not prove that
production can create an active principal with `corridor_disposition_authority=true`. The existing
activation-principal constitution route still fixes that scope to false.

## Result non-authorities

Every reconstruction result is `read_only=true` and records:

- `authority_created=false`;
- `authority_issued=false`;
- `authority_consumed=false`;
- `disposition_selected=false`;
- `disposition_sealed=false`;
- `source_artifact_mutated=false`;
- `successor_authority_created=false`; and
- `external_action_performed=false`.

The result contains exact references only. It does not return credential-bearing evidence or a
capability.

## Preserved perimeter

No caller authority is issued or consumed. No disposition is selected or sealed. No principal or
binding is activated; no activation artifact is mutated, consumed, revoked, repaired or
reinterpreted; no successor authority is created; no capability or credential is handled; no
credential platform is selected; no provider is invoked; no external I/O occurs; and Iron Gate and
Lazaretto remain closed. `REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE` remains authoritative and
Provider Execution Assurance remains paused.
