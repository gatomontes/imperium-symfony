# Next lifecycle: Persona retrieval and deployment

## Starting checkpoint

Persona production is complete. The canonical 33-step route ends with the exact Persona admitted into Garrison as `ADMITTED_HELD`. Production, Senate confirmation, Guildhall fulfillment, and Garrison admission are implemented and covered by the cumulative acceptance test.

## Governing separation

The next lifecycle must not extend production by implication. It begins only after a new, explicit personnel-use demand.

The central boundary is:

**An admitted Persona is stored cognitive identity—not an operative, manifestation, Seat occupant, or authorization to work.**

## Provisional route for design

**Authorized personnel-use demand → Guildhall suitability against Garrison facts → Garrison reservation/retrieval → Laboratorium Profile derivation → competent Profile approval → Conscription manifestation assembly and qualification → authorized Seat binding or deployment → governed return/retirement**

This route is provisional. Before implementation, jurisdiction must be settled for requester authority, suitability selection, Profile stewardship and approval, deployment authorization, and post-use custody transitions.

## Capability-to-Profession Translation Boundary

The first jurisdiction is settled:

- Curia and every upstream mission-governance surface speak only in functional skills, attributes, capabilities, constraints, and expected outcomes.
- Curia records these in `capability_requirements`. It has no profession-selection or Persona-selection authority.
- Guildhall exclusively translates the exact capability demand into professions and Persona suitability criteria.
- From Guildhall downward, the personnel lifecycle speaks in professions, exact Personas, versions, provenance, suitability, custody, and availability.
- Garrison reports exact custody and availability facts but neither translates capabilities nor determines professional suitability.
- Guildhall's translation and suitability determination do not authorize retrieval, Profile derivation, manifestation assembly, deployment, or execution.

The governed handoff is therefore:

**Curia capability demand → Guildhall profession determination → Guildhall suitability against Garrison facts**

This boundary is named `CAPABILITY_TO_PROFESSION` and is owned by `guildhall.guildmaster`.

## First design question — resolved

The initiating mission artifact carries capability requirements rather than profession or Persona selections. Guildhall owns capability-to-profession translation and determines Persona suitability against Garrison facts. A later batch must still define the distinct authorization that permits reservation and retrieval of the resulting exact Persona.

## Personnel-use authorization dialogue — implemented

After Guildhall resolves exact personnel against capability slots, Curia presents the functional commitments together with Guildhall's profession determination and exact Persona identity to Imperator. Curia knows and records that resolution solely in a presentation capacity: it cannot select, rank, substitute, or amend either profession or Persona.

Imperator therefore knows exactly which profession and Persona he is being asked to authorize. The authorization request and resulting act are bound to the complete Guildhall disposition, exact Persona custody identity, profession, capability correlation, and resolution digests. Any changed profession or replacement Persona requires a new Guildhall disposition and a new Imperator authorization.

Imperator may record exactly one disposition against each exact request:

- `AUTHORIZED`;
- `REFUSED`;
- `RETURNED_FOR_REVISION`;
- `ALTERNATIVE_PROPOSED`;
- `CLARIFICATION_REQUIRED`; or
- `DEFERRED`.

Every disposition preserves Imperator's exact response and optional limitations. Only `AUTHORIZED` creates `personnel_use_authority`; objection, suggestion, clarification, deferral, and refusal remain mechanically non-authorizing. An alternative does not mutate the Guildhall disposition or authorize itself. Competent revision must produce a new digest-bound request for a later explicit decision.

## Guildhall acceptance and Garrison reservation — implemented

Guildhall may accept only an exact `AUTHORIZED` Imperator act whose request, disclosed profession, exact Persona custody identity, suitability determination, capability correlation, Guildhall disposition, and digests remain unchanged. Acceptance makes one exact Garrison reservation request permissible for each authorized Persona. Guildhall cannot reserve, retrieve, substitute, or deploy the Persona.

The occupied Constable independently verifies the exact request chain, admitted custody record, Persona identity, instance, custodial state, availability, and existing reservations. Garrison records either `RESERVED_PENDING_PROFILE_DERIVATION_AUTHORIZATION` or a factual non-authorizing refusal: `REFUSED_PERSONA_NOT_ADMITTED`, `REFUSED_PERSONA_UNAVAILABLE`, `REFUSED_PERSONA_ALREADY_RESERVED`, or `REFUSED_DISPOSITION_MISMATCH`. Garrison cannot select or propose another Persona.

The current implementation stops at `RESERVED_PENDING_PROFILE_DERIVATION_AUTHORIZATION`. Reservation preserves Garrison custody and grants no retrieval, Profile derivation, manifestation assembly, Seat binding, deployment, or execution authority.

## Non-negotiable inherited invariants

- preserve the exact admitted Persona ID, version, digest, custody record, Guildhall commission, and Senate confirmation;
- Garrison reports custody and availability facts but does not determine professional suitability or deployment;
- Guildhall suitability does not itself authorize retrieval or use;
- retrieval does not authorize Profile derivation, manifestation assembly, Seat binding, deployment, or execution;
- Laboratorium transforms but does not approve or deploy its own Profile;
- Conscription assembles and qualifies but does not select personnel or authorize their use;
- a generic Officer substrate contributes no independent identity, jurisdiction, or mission authority;
- every authority transition must be explicit, attributable, digest-bound, and independently revocable;
- return, retirement, supersession, and custody restoration must be defined before deployment is allowed.
