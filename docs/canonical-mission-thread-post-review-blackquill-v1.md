# Canonical Mission Thread post-review — Blackquill v1

`CANDIDATE_IMPLEMENTATION_QUARANTINED`
`MISSION_THREAD_SHAPE_PROVED`
`OPERATOR_AUTHORITY_PROVENANCE_NOT_PROVED`
`CALLER_SUPPLIED_VERIFIER_BYPASS_PRESENT`
`REFERENCE_MISSION_SIMULATED_NOT_ACTUAL`
`FORMAL_CLOSURE_REFUSED`
`MERGE_NOT_AUTHORIZED`

## Reviewed range

- Accepted baseline: `2527b33925bf3ef47d029786e60a6aefe752737b`
- Quarantined branch: `codex/canonical-mission-thread-authority-provenance`
- Reviewed head: `3c4890ffd30f403f72a35b92f1e639d51c8c98f8`
- Range: seven commits ahead, zero behind
- No pull request or merge was created for the quarantined branch

## Material findings

1. `OperatorMissionBoundary` accepts a caller-created dossier whose provenance merely names
   `operator-mission-order`, then creates its own key and capabilities. No authenticated
   Operator order, exact approval record or established trust root is verified.
2. The reconciliation writer accepts both `MissionCapability` and
   `MissionCapabilityConsumer` from its caller. A caller-controlled consumer can accept a
   fabricated capability and return an invented consumption record.
3. The reference mission compares an asserted commit string while inspecting caller-supplied
   in-memory bytes. The bytes are not derived from or verified against the named Git tree/blobs.
4. The Fiber proof starts two non-suspending Fibers sequentially. It proves same-object sequential
   replay refusal, not concurrent or cross-process consumption.
5. Mission-transition capabilities are pre-issued together and are not mechanically bound to the
   required lifecycle state at consumption.

## Retained value

The candidate supplies useful vocabulary, mission identity propagation, receipt/status shapes and
a removed corridor factory. Those ideas may be recovered selectively. The candidate branch is
adverse evidence and must not be merged or treated as accepted substrate.

## Required correction

Use the established Mission Authorization chain as the authority source; resolve verification from
trusted runtime wiring rather than caller input; bind snapshot bytes to actual Git objects; enforce
durable state-bound consumption; prove real process contention; and require a distinct Operator
authorization event before executing the reference mission.
