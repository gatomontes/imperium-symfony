# Handoff: Delegate Mission Step 15 complete

## Transition

The current occupied Lord Speaker (`LEGATE`) independently accepts or refuses the examination-preparation handoff for the mission-bound `DELEGATE`.

Senate revalidates the exact handoff, candidate, Persona, custody lease, examination-only assembly contract, current Garrison custody, and Lord Speaker occupancy. Classification alone grants no intake authority.

Acceptance consumes the Lord Speaker's single-use intake authority and opens one exact single-use examination-only assembly authority for Conscription. No Manifestation is assembled, and Senate examination remains unauthorized. Refusal grants nothing.

## Checkpoints

- Accepted: `DELEGATE_MISSION_EXAMINATION_PREPARATION_ACCEPTED_PENDING_CONSCRIPTION_ASSEMBLY`
- Refused: `DELEGATE_MISSION_EXAMINATION_PREPARATION_REFUSED_NO_AUTHORITY`

## Implementation

- `src/Imperium/Runtime/Senate/DelegateMissionExaminationPreparationIntakeDispositionService.php`
- Step 15 coverage in `tests/Imperium/Runtime/DelegateMissionGuildhallResolutionFlowTest.php`
- `contracts/delegate-mission-examination-preparation-intake-disposition.md`

## Next bounded transition

Delegate Mission Step 16 is Conscription's assembly and delivery of exactly one examination-only Manifestation to the Senate Stand. It must consume the Step 15 authority and grant no mission Seat, deployment, operational-use, or execution authority.
