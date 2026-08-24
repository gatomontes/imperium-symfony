# Handoff: Delegate Mission Step 7 complete

## Transition

The exact active `LEGATE` Constable independently decides the Step 6 reservation request against live Garrison facts and both Delegate and legacy reservation ledgers.

Factual dispositions are:

- `RESERVED`;
- `PERSONA_NOT_ADMITTED`;
- `PERSONA_UNAVAILABLE`;
- `PERSONA_ALREADY_RESERVED`; or
- `DISPOSITION_MISMATCH`.

Garrison cannot rank, select, or propose another Persona.

## Checkpoints

- Success: `DELEGATE_MISSION_PERSONA_RESERVED_PENDING_PROFILE_SCOPE_CONSTRUCTION`
- Refusals: exact `DELEGATE_MISSION_RESERVATION_REFUSED_*_NO_AUTHORITY` checkpoints.

Success commits only the reservation. The Persona remains in `ADMITTED_HELD` Garrison custody. One single-use Curia authority opens for construction of the immutable Profile-scope request; Profile derivation itself remains unauthorized.

All refusal branches grant nothing. No branch grants retrieval, custody transfer, Profile derivation, examination, approval, installation, Manifestation assembly, mission Seat binding, deployment, cognition, provider invocation, data/tool/credential use, perimeter crossing, external action, execution, or continuing authority.

## Implementation

- `src/Imperium/Runtime/Garrison/DelegateMissionPersonaReservationDispositionService.php`
- Step 7 coverage in `tests/Imperium/Runtime/DelegateMissionGuildhallResolutionFlowTest.php`
- `contracts/delegate-mission-persona-reservation.md`

## Next bounded transition

Delegate Mission Step 8 is Curia's construction of the immutable Profile-scope authorization request from the exact successful reservation and original Mission Plan. Curia must not alter the profession, Persona, mission Seat, capabilities, duration, resources, stop conditions, or terminal lifecycle design.
