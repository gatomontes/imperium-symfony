# Handoff: Delegate Mission Step 5 complete

## Transition

The Imperator records one attributable decision against the exact sealed Step 4 Delegate personnel-use request.

Permitted dispositions are:

- `AUTHORIZED`;
- `REFUSED`;
- `RETURNED_FOR_REVISION`;
- `ALTERNATIVE_PROPOSED`;
- `CLARIFICATION_REQUIRED`; or
- `DEFERRED`.

Only `AUTHORIZED`, with explicit limitations, creates one exact single-use personnel-use authority held by `guildhall.guildmaster`. The authority remains unconsumed pending Guildhall acceptance and is digest-bound to the complete personnel commitment.

## Checkpoints

- Authorized: `DELEGATE_MISSION_PERSONNEL_USE_AUTHORIZED_PENDING_GUILDHALL_ACCEPTANCE`
- Every other branch: `DELEGATE_MISSION_NON_AUTHORIZING_IMPERATOR_PERSONNEL_USE_DISPOSITION_RECORDED`

Authorization itself does not constitute Guildhall acceptance and grants no reservation, retrieval, custody transfer, Profile lifecycle, Manifestation assembly, mission Seat binding, deployment, operational use, cognition, provider invocation, data/tool/credential use, perimeter crossing, external action, execution, or continuing authority.

## Implementation

- `src/Imperium/Runtime/Imperator/DelegateMissionPersonnelUseDecisionService.php`
- Step 5 coverage in `tests/Imperium/Runtime/DelegateMissionGuildhallResolutionFlowTest.php`
- `contracts/delegate-mission-personnel-use-decision.md`

## Next bounded transition

Delegate Mission Step 6 is Guildhall acceptance of the exact authorized personnel commitment and issuance of one reservation request to Garrison. Guildhall acceptance must consume the Step 5 authority without itself reserving, retrieving, or transferring custody.
