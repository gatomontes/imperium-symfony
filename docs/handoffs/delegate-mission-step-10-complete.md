# Handoff: Delegate Mission Step 10 complete

## Transition

The occupied permanent Conscription Recruiter (`LEGATE`) independently accepts or refuses the Step 9 authority for the temporary mission Officer (`DELEGATE`). Classification itself grants neither actor authority.

Acceptance consumes the exact single-use authority and constructs one Laboratorium commission request bound to the immutable Profile scope, the successful reservation, and live sealed `ADMITTED_HELD` custody. Garrison retains custody; Conscription cannot substitute the Persona or amend the scope.

Laboratorium has not accepted the request, so Profile derivation remains non-exercisable.

## Checkpoints

- Accepted: `DELEGATE_MISSION_PROFILE_DERIVATION_ACCEPTED_COMMISSION_REQUESTED_PENDING_ALCHEMIST_ACCEPTANCE`
- Commission request: `DELEGATE_MISSION_PROFILE_DERIVATION_COMMISSION_REQUESTED_PENDING_ALCHEMIST_ACCEPTANCE`
- Refused: `DELEGATE_MISSION_PROFILE_DERIVATION_AUTHORIZATION_REFUSED_BY_CONSCRIPTION_NO_AUTHORITY`

## Implementation

- `src/Imperium/Runtime/Conscription/DelegateMissionProfileDerivationCommissionRequestService.php`
- Step 10 coverage in `tests/Imperium/Runtime/DelegateMissionGuildhallResolutionFlowTest.php`
- `contracts/delegate-mission-profile-derivation-commission-request.md`

## Next bounded transition

Delegate Mission Step 11 is the occupied Alchemist's acceptance or refusal of the exact custody-bound commission. Acceptance may make the single derivation authority exercisable but must stop before any Profile is derived.
