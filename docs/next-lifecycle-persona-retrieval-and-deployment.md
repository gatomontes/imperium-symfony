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

## Profile-derivation authorization — implemented

Curia may present an authorization request only when one exact Persona is successfully reserved. The request binds that reservation to the exact structured Mission Plan turn and carries the immutable mission objective, scope, constraints, capability requirements, tools, data, and stop conditions into one proposed Profile scope. Fresh CLI prose cannot redefine the Profile after personnel selection.

The current explicit constitutional route identifies Curia as mission-Profile steward, Conscription as the prospective commissioner and installer, Laboratorium as transformer, Senate as examiner, and Imperator as approver. Imperator may authorize, refuse, return for revision, propose an alternative, request clarification, or defer. Only `AUTHORIZED` creates Profile-derivation authority.

The implementation stops at `PROFILE_DERIVATION_AUTHORIZED_PENDING_CONSCRIPTION_ACCEPTANCE`. Garrison retains the reservation, and the act grants no retrieval, Conscription acceptance, Laboratorium commission, Profile artifact, manifestation assembly, examination disposition, Seat binding, deployment, or execution authority. This deliberately explicit checkpoint may later be covered by a bounded Imperator delegation without changing the underlying authority chain.

## Conscription acceptance and derivation handoff request — implemented

The occupied ordinary Recruiter may accept only an exact `AUTHORIZED` Profile-derivation act whose Curia request, successful Garrison reservation, structured Mission Plan source, exact Persona, profession, capability commitment, Profile scope, jurisdiction assignments, and record digests remain unchanged. Acceptance binds Conscription to the authorized derivation route; it does not move custody or commission Laboratorium.

Acceptance permits one exact request to the occupied Constable for a custody-bound, derivation-only Persona handoff. The request preserves the complete authority chain and stops at `PENDING_CONSTABLE_PROFILE_DERIVATION_HANDOFF_DISPOSITION`. It grants no handoff, retrieval, custody release, Laboratorium commission, Profile artifact, manifestation assembly, Senate examination, Seat binding, deployment, or execution authority. Garrison must independently decide the handoff in the next batch.

## Constable derivation-handoff disposition — implemented

The occupied Constable independently validates the exact Conscription request, acceptance, Imperator act, successful reservation, immutable Profile scope, admitted Persona custody record, availability, instance, and complete digest chain. The Constable may record `APPROVED` or `REFUSED` with an attributable rationale.

Approval creates only a custody-bound Profile-derivation lease and authority for Conscription to issue the next exact Laboratorium-commission request. Garrison retains the Persona in `ADMITTED_HELD`; there is no unrestricted retrieval or custody release. Refusal creates no downstream authority. The implementation stops at `PROFILE_DERIVATION_HANDOFF_APPROVED_PENDING_CONSCRIPTION_LABORATORIUM_COMMISSION`, before any Laboratorium commission, Profile artifact, manifestation assembly, Senate examination, Seat binding, deployment, or execution.

## Conscription Profile-derivation commission — implemented

The occupied ordinary Recruiter may commission Laboratorium only from an exact approved Constable handoff disposition. Conscription revalidates the complete disposition, handoff request, acceptance, reservation, custody record, immutable Profile scope, Persona identity, instance, and digest chain before issuing one commission to `laboratorium.alchemist`.

The commission is limited to `DERIVE_ONE_EXACT_MISSION_PROFILE`, preserves Garrison custody at `ADMITTED_HELD`, and requires return to Conscription. It carries Profile-derivation authority but that authority is not exercisable until the occupied Alchemist accepts the exact commission. The implementation stops at `PENDING_ALCHEMIST_PROFILE_DERIVATION_COMMISSION_ACCEPTANCE`; no Profile artifact, approval, installation, manifestation assembly, Senate examination, Seat binding, deployment, or execution exists.

## Alchemist commission acceptance — implemented

The occupied Alchemist independently validates the exact commission, approved Constable lease, immutable Persona, custody lease, Profile scope, return destination, instance, active Laboratorium occupancy, and complete digest chain. Only that occupied Seat may accept the commission.

Acceptance changes the exact commission's Profile-derivation authority from non-exercisable to exercisable and grants authority to create one derived Profile candidate. It does not itself derive or create the candidate. The implementation stops at `PROFILE_DERIVATION_COMMISSION_ACCEPTED_PENDING_PROFILE_DERIVATION`; Garrison custody remains `ADMITTED_HELD`, and no Profile artifact, approval, installation, manifestation, Senate examination, Seat binding, deployment, or execution exists.

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
