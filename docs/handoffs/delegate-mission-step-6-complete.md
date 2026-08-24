# Handoff: Delegate Mission Step 6 complete

## Transition

The same exact active `LEGATE` Guildmaster accepts the exact `AUTHORIZED` Step 5 personnel commitment, consumes its single-use personnel-use authority, and issues one immutable reservation request to `garrison.constable`.

The acceptance and reservation request preserve unchanged:

- the Step 5 decision and explicit Imperator limitations;
- the Step 4 request;
- the Step 3 resolution and Guildmaster identity;
- the Step 1 capability and Mission Plan lineage; and
- the exact `DELEGATE` personnel commitment, including Persona custody identity.

## Checkpoints

- Acceptance and request: `DELEGATE_MISSION_PERSONNEL_USE_AUTHORIZATION_ACCEPTED_RESERVATION_REQUESTED_PENDING_CONSTABLE_DISPOSITION`
- Garrison inbox request: `DELEGATE_MISSION_PERSONA_RESERVATION_REQUESTED_PENDING_CONSTABLE_DISPOSITION`

No reservation has occurred. Garrison remains the independent decision-maker over custody and availability facts. Guildhall has no reservation, retrieval, custody-transfer, Profile, assembly, Seat-binding, deployment, cognition, resource-use, external-action, or execution authority.

## Implementation

- `src/Imperium/Runtime/Guildhall/DelegateMissionPersonnelUseAcceptanceService.php`
- Step 6 coverage in `tests/Imperium/Runtime/DelegateMissionGuildhallResolutionFlowTest.php`
- `contracts/delegate-mission-personnel-use-acceptance.md`

## Next bounded transition

Delegate Mission Step 7 is the Constable's exact reservation disposition. The Constable must revalidate live custody, admission, availability, instance, identity, and conflicts. Success reserves without retrieval or custody transfer; factual refusal grants nothing.
