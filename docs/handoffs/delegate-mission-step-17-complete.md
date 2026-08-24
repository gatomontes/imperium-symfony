# Handoff: Delegate Mission Step 17 complete

## Transition

The current occupied Bailiff (`LEGATE`) independently admits or refuses the examination-only Manifestation of the mission-bound `DELEGATE` at the Senate Stand.

The Bailiff revalidates the exact delivery, candidate, examination-only installation, Manifestation restrictions, current custody, and security occupancy. Classification alone grants no admission authority.

Admission consumes the exact Stand intake authority, secures the proceeding, and opens one single-use examination-opening authority for the Lord Speaker. The examination has not opened; no questioning, cognition, testimony, or findings are authorized. Refusal grants nothing.

## Checkpoints

- Admitted: `DELEGATE_MISSION_EXAMINATION_MANIFESTATION_ADMITTED_SECURED_PENDING_EXAMINATION_OPENING`
- Refused: `DELEGATE_MISSION_EXAMINATION_MANIFESTATION_REFUSED_AT_STAND_NO_AUTHORITY`

## Implementation

- `src/Imperium/Runtime/Senate/DelegateMissionExaminationStandAdmissionDispositionService.php`
- Step 17 coverage in `tests/Imperium/Runtime/DelegateMissionGuildhallResolutionFlowTest.php`
- `contracts/delegate-mission-examination-stand-admission.md`

## Next bounded transition

Delegate Mission Step 18 is the occupied Lord Speaker's opening of one bounded Profile examination. Opening must consume the Step 17 authority and define the hearing contract before any question or cognition occurs.
