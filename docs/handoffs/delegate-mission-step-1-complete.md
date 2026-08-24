# Handoff: Delegate Mission Step 1 complete

## Lifecycle boundary

This is Step 1 of the separately named Delegate mission route. It is not Step 11 of the terminally closed operational-adoption lifecycle and derives no authority from an adoption disposition.

## Implemented transition

Curia mechanically seals one exact mission-bound capability demand from an existing Mission Authorization. Runtime independently revalidates:

- the immutable `imperium.mission-authorization/v1` record;
- the exact approved planning dossier and embedded Mission Plan;
- the exact affirmative Imperator dossier review;
- the consumed Mission Authorization derivation authority;
- instance, version, identity, and digest continuity; and
- the absence of deployment, external effect, or execution under the source authorization.

The Mission Plan must already state objective, scope, deliverables, constraints, inputs, functional capabilities, expected outcomes, intended mission Seat, bounded duration, data/tool/credential/perimeter requirements, stop conditions, and return, unbinding, custody-restoration, and retirement conditions. No CLI or other fresh prose can redefine them.

The output explicitly carries `officer_class: DELEGATE`, names `guildhall.guildmaster` as the pending consumer, and preserves the `CAPABILITY_TO_PROFESSION` boundary. Curia-selected profession or Persona fields fail closed.

## Checkpoint

`DELEGATE_MISSION_CAPABILITY_DEMAND_SEALED_PENDING_GUILDHALL_INTAKE_NO_PERSONNEL_AUTHORITY`

Step 1 does not deliver the demand or authorize Guildhall intake. It grants no Mission Plan amendment, profession translation or determination, Persona selection or suitability, personnel use, reservation, retrieval, custody transfer, Profile lifecycle, Manifestation assembly, mission Seat binding, commissioning, follow-up commissioning, deployment, operational use, cognition, provider invocation, data access, tool or credential use, perimeter crossing, external action, execution, return execution, or continuing turn.

## Implementation

- `src/Imperium/Runtime/Curia/DelegateMissionCapabilityDemandService.php`
- `tests/Imperium/Runtime/DelegateMissionCapabilityDemandServiceTest.php`
- `contracts/delegate-mission-capability-demand.md`

## Next separate transition

Delegate Mission Step 2 must decide exact Guildhall intake. Receipt or naming a consumer must not imply intake, profession determination, Persona suitability, personnel use, or any later authority.
