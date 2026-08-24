# Handoff: Delegate Mission Step 12 complete

## Transition

The same occupied Alchemist that accepted the commission consumes its exact single-use derivation authority, performs bounded Profile-elaboration cognition, and seals one versioned Delegate Profile candidate.

The candidate preserves the exact Persona, profession, mission Seat, objective, scope, deliverables, capabilities, duration, resources, constraints, stop conditions, and terminal return/unbinding/custody-restoration/retirement design. Live Garrison custody and Alchemist occupancy are revalidated immediately before derivation.

Laboratorium then returns the sealed candidate to Conscription. The return opens only a single-use intake-disposition authority for the occupied Recruiter.

## Checkpoints

- Candidate: `DELEGATE_MISSION_PROFILE_CANDIDATE_DERIVED_VERSIONED_SEALED`
- Return: `DELEGATE_MISSION_PROFILE_CANDIDATE_RETURNED_PENDING_CONSCRIPTION_INTAKE`

The candidate is not approved, active, installed, examined, assembled, bound, deployed, or operational. Mission cognition and all resource/external-action authorities remain false.

## Implementation

- `src/Imperium/Runtime/Laboratorium/DelegateMissionProfileCandidateDerivationReturnService.php`
- shared Profile-elaboration cognition gateway wording supports both governed derivation routes
- Step 12 coverage in `tests/Imperium/Runtime/DelegateMissionGuildhallResolutionFlowTest.php`
- `contracts/delegate-mission-profile-candidate-derivation-return.md`

## Next bounded transition

Delegate Mission Step 13 is Conscription's intake disposition on the returned candidate. Acceptance must verify exact lineage and custody, consume only the intake authority, and determine the next examination-preparation handoff without approving or installing the Profile.
