# Handoff: Delegate Mission Step 8 complete

## Transition

Curia consumes the exact single-use construction authority opened by a successful Step 7 reservation and mechanically constructs one immutable, identity-bearing Delegate Profile-scope authorization request.

The complete sealed lineage is revalidated through the reservation request, Guildhall acceptance, Imperator personnel-use authorization, Curia presentation, Guildhall resolution, and original capability demand. Curia cannot alter or substitute the profession, Persona, mission Seat, capabilities, duration, resource requirements, or terminal lifecycle conditions.

## Checkpoint

`DELEGATE_MISSION_PROFILE_SCOPE_REQUEST_PRESENTED_PENDING_IMPERATOR_DECISION`

The request is addressed to Imperator. No Profile has been derived, instantiated, activated, examined, or approved. No Delegate has been assembled, bound, deployed, or authorized to operate.

## Implementation

- `src/Imperium/Runtime/Curia/DelegateMissionProfileScopeAuthorizationRequestService.php`
- Step 8 coverage in `tests/Imperium/Runtime/DelegateMissionGuildhallResolutionFlowTest.php`
- `contracts/delegate-mission-profile-scope-authorization-request.md`

## Next bounded transition

Delegate Mission Step 9 is Imperator's explicit decision on the exact immutable Profile scope. Only an exact authorizing disposition may open one bounded Profile-derivation authority; every other disposition remains non-authorizing.
