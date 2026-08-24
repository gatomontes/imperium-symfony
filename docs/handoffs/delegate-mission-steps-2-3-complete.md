# Handoff: Delegate Mission Steps 2 and 3 complete

## Step 2 — Guildhall capability-demand intake disposition

The exact active `LEGATE` Guildmaster independently accepts or refuses the exact sealed Step 1 demand.

- Acceptance: `DELEGATE_MISSION_CAPABILITY_DEMAND_ACCEPTED_PENDING_PROFESSION_AND_PERSONA_SUITABILITY_RESOLUTION`
- Refusal: `DELEGATE_MISSION_CAPABILITY_DEMAND_REFUSED_NO_PERSONNEL_AUTHORITY`

Only acceptance opens one recipient-bound, single-use personnel-resolution authority. No personnel has been resolved or authorized at Step 2.

## Step 3 — profession and Persona suitability resolution

The same Guildmaster consumes the Step 2 authority, translates the exact functional capabilities into a profession, and determines Persona suitability only against an unchanged authoritative Garrison inventory response.

Branches:

- `SUITABLE` → `DELEGATE_MISSION_PROFESSION_AND_PERSONA_SUITABILITY_RESOLVED_PENDING_PERSONNEL_USE_REQUEST`
- `NO_SUITABLE_PERSONA` → `DELEGATE_MISSION_PROFESSION_RESOLVED_PERSONNEL_GAP_IDENTIFIED_NO_PERSONNEL_AUTHORITY`
- `UNRESOLVED` → `DELEGATE_MISSION_PERSONNEL_RESOLUTION_UNRESOLVED_NO_PERSONNEL_AUTHORITY`

The suitable branch opens only one single-use authority to present the exact identity-bearing personnel-use request to Curia. It does not grant personnel-use authority.

All branches grant no reservation, retrieval, custody transfer, Profile lifecycle, Manifestation assembly, mission Seat binding, deployment, cognition, provider invocation, data/tool/credential use, perimeter crossing, external action, execution, or continuing turn.

## Implementation

- `src/Imperium/Runtime/Guildhall/DelegateMissionCapabilityDemandIntakeService.php`
- `src/Imperium/Runtime/Guildhall/DelegateMissionPersonnelResolutionService.php`
- `tests/Imperium/Runtime/DelegateMissionGuildhallResolutionFlowTest.php`
- `contracts/delegate-mission-guildhall-resolution.md`

## Next bounded transition

Delegate Mission Step 4 is Curia's identity-bearing personnel-use presentation. Curia must preserve Guildhall's profession, exact Persona, suitability disposition, Garrison facts, capability correlation, and digests unchanged. Presentation is not an Imperator decision and grants no personnel-use authority.
