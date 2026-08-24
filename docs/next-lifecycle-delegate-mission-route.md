# Next lifecycle: Delegate mission route

## Purpose

The next implementation leg governs temporary mission-bound Officers from demand through deployment, bounded work, return, unbinding, and termination.

A mission Delegate is not a permanent Office Legate. It exists only for the exact mission authority, Seat, duration, scope, and termination conditions recorded for it.

## Starting boundaries

- Operational adoption is complete and terminal.
- An adopted result does not amend a Mission Plan or authorize work.
- An admitted Persona remains stored cognitive identity, not an operative.
- Curia states capabilities and mission need; it does not select a profession or Persona.
- Guildhall owns capability-to-profession translation and Persona suitability.
- Garrison owns Persona custody and availability facts.
- Conscription assembles and qualifies; it does not select personnel or authorize use.
- Imperator decides protected personnel-use, Profile, deployment, tool, credential, perimeter, and action commitments where competent authority is required.

## Estimated route

The Delegate mission route is expected to require approximately `14–18` bounded transitions. The exact count must follow the authority chain rather than an arbitrary target.

The expected phases are:

1. mission-bound capability demand;
2. Guildhall profession and Persona resolution;
3. identity-bearing personnel-use authorization;
4. Garrison reservation and custody-bound Profile derivation;
5. mission Profile derivation, examination, approval, and qualification;
6. Delegate assembly and exact mission Seat binding;
7. deployment authorization and custody transition;
8. bounded mission cognition and separately authorized resources;
9. tool, credential, perimeter, and external-action gating where required;
10. governed result delivery and mission disposition;
11. return authorization and return execution;
12. Seat unbinding, credential revocation, custody restoration, and Delegate termination.

Existing implemented Persona-retrieval and deployment records must be reused only when their exact contract and officer class are competent for the Delegate route. Permanent Legate activation must not be copied by implication.

## First bounded transition

### Name

`Delegate Mission Step 1 — mission-bound capability demand`

### Implementation status

Implemented. See `docs/handoffs/delegate-mission-step-1-complete.md` and `contracts/delegate-mission-capability-demand.md`.

### Intended producer and decision source

Curia mechanically seals the demand only from an exact approved Mission Plan and whatever separate authorization record makes the mission requirement competent to proceed. Fresh CLI prose cannot redefine the plan.

### Intended consumer

Guildhall receives the capability demand for later profession determination and Persona suitability resolution. Step 1 does not itself commission or deliver to Guildhall unless that authority is explicitly present in the source records.

### Exact content

The demand should bind:

- instance and Mission Plan identity and digest;
- objective, scope, deliverables, constraints, and required inputs;
- capability requirements and expected outcomes;
- intended mission Seat and bounded duration;
- data, tool, credential, and perimeter requirements;
- stop conditions;
- return, unbinding, custody-restoration, and retirement conditions; and
- explicit `officer_class: DELEGATE`.

It must contain capabilities, not a Curia-selected profession or Persona.

### Proposed checkpoint

`DELEGATE_MISSION_CAPABILITY_DEMAND_SEALED_PENDING_GUILDHALL_INTAKE_NO_PERSONNEL_AUTHORITY`

### Authorities remaining false

- profession determination;
- Persona selection or suitability disposition;
- personnel-use authorization;
- reservation or custody transfer;
- Profile derivation or approval;
- Manifestation assembly;
- mission Seat binding;
- deployment;
- cognition or provider invocation;
- credential or tool use;
- perimeter crossing;
- external action or execution;
- continuing-turn authority.

## Next bounded transition

`Delegate Mission Step 2 — Guildhall capability-demand intake disposition`

The occupied competent Guildmaster must independently accept or refuse the exact sealed Step 1 demand before capability-to-profession translation or Persona suitability becomes possible. Step 1 naming Guildhall as consumer is not delivery, intake, acceptance, or authority.

### Implementation status

Steps 2 and 3 are implemented. See `docs/handoffs/delegate-mission-steps-2-3-complete.md` and `contracts/delegate-mission-guildhall-resolution.md`.

Step 2 acceptance opens only one exact single-use resolution authority. Step 3 consumes it to determine a profession and Persona suitability against authoritative Garrison custody and availability facts. A suitable resolution opens only an identity-bearing personnel-use request authority; personnel use itself remains unauthorized.

## Following bounded transition

`Delegate Mission Step 4 — identity-bearing personnel-use presentation`

Curia presents Guildhall's unchanged functional correlation, profession, exact Persona, suitability determination, and Garrison fact lineage for later Imperator decision. Curia may not select, rank, substitute, or amend them.

### Implementation status

Step 4 is implemented. See `docs/handoffs/delegate-mission-step-4-complete.md` and `contracts/delegate-mission-personnel-use-request.md`.

The checkpoint is `DELEGATE_MISSION_PERSONNEL_USE_REQUEST_PRESENTED_PENDING_IMPERATOR_DECISION`. Presentation consumes only the exact Step 3 request authority and grants no personnel-use or operational authority.

## Next bounded transition

`Delegate Mission Step 5 — Imperator personnel-use decision`

Only an explicit `AUTHORIZED` decision against the exact Step 4 request may grant one bounded personnel-use authority. Every other disposition remains non-authorizing, and authorization itself must stop before Guildhall acceptance or Garrison reservation.

### Implementation status

Step 5 is implemented. See `docs/handoffs/delegate-mission-step-5-complete.md` and `contracts/delegate-mission-personnel-use-decision.md`.

Only `AUTHORIZED` with explicit limitations reaches `DELEGATE_MISSION_PERSONNEL_USE_AUTHORIZED_PENDING_GUILDHALL_ACCEPTANCE`. Every other disposition is sealed and non-authorizing.

## Next bounded transition

`Delegate Mission Step 6 — Guildhall acceptance and Garrison reservation request`

The exact Guildmaster must accept the unchanged authorized commitment, consume the Step 5 personnel-use authority, and issue one exact reservation request. Neither acceptance nor the request may reserve, retrieve, or transfer custody; the Constable decides those facts and effects separately.

### Implementation status

Step 6 is implemented. See `docs/handoffs/delegate-mission-step-6-complete.md` and `contracts/delegate-mission-personnel-use-acceptance.md`.

The route stops at `DELEGATE_MISSION_PERSONNEL_USE_AUTHORIZATION_ACCEPTED_RESERVATION_REQUESTED_PENDING_CONSTABLE_DISPOSITION`. The Persona remains unreserved and held by Garrison.

## Next bounded transition

`Delegate Mission Step 7 — Constable reservation disposition`

The occupied Constable independently revalidates the exact request and live custody ledger. Success may reserve the exact Persona while custody remains `ADMITTED_HELD`; refusal branches report factual admission, availability, conflict, or lineage failures without proposing a substitute.

### Implementation status

Step 7 is implemented. See `docs/handoffs/delegate-mission-step-7-complete.md` and `contracts/delegate-mission-persona-reservation.md`.

Success reaches `DELEGATE_MISSION_PERSONA_RESERVED_PENDING_PROFILE_SCOPE_CONSTRUCTION`. It opens only one exact Curia Profile-scope construction authority; Profile derivation, retrieval, and custody transfer remain unauthorized.

## Next bounded transition

`Delegate Mission Step 8 — immutable Delegate Profile-scope authorization request`

Curia consumes the exact Step 7 scope-construction authority and binds the reservation to the original approved Mission Plan, profession, Persona, mission Seat, duration, capabilities, resource requirements, stop conditions, and return/unbinding/custody-restoration/retirement design. The result is presented for Imperator decision without deriving a Profile.

### Implementation status

Step 8 is implemented. See `docs/handoffs/delegate-mission-step-8-complete.md` and `contracts/delegate-mission-profile-scope-authorization-request.md`.

The route stops at `DELEGATE_MISSION_PROFILE_SCOPE_REQUEST_PRESENTED_PENDING_IMPERATOR_DECISION`. Construction consumes only the exact Step 7 authority and grants no Profile derivation or operational authority.

## Next bounded transition

`Delegate Mission Step 9 — Imperator Profile-scope authorization decision`

Imperator decides the exact immutable Step 8 request. Only an explicit authorizing disposition may open one bounded derivation authority for that exact scope; refusal, return, alternatives, clarification, and deferral remain non-authorizing.

### Implementation status

Step 9 is implemented. See `docs/handoffs/delegate-mission-step-9-complete.md` and `contracts/delegate-mission-profile-scope-decision.md`.

Authorization stops at `DELEGATE_MISSION_PROFILE_DERIVATION_AUTHORIZED_PENDING_CONSCRIPTION_ACCEPTANCE`. It opens one exact single-use authority held by Conscription; no Profile has yet been derived.

## Next bounded transition

`Delegate Mission Step 10 — Conscription acceptance and custody-bound Laboratorium commission request`

The occupied Recruiter accepts the unchanged Step 9 authorization, consumes its exact authority, and constructs the custody-bound request needed to commission Laboratorium. This transition must stop before Laboratorium acceptance or Profile derivation.

### Implementation status

Step 10 is implemented. See `docs/handoffs/delegate-mission-step-10-complete.md` and `contracts/delegate-mission-profile-derivation-commission-request.md`.

The accepted route stops at `DELEGATE_MISSION_PROFILE_DERIVATION_COMMISSION_REQUESTED_PENDING_ALCHEMIST_ACCEPTANCE`. Garrison retains custody, and Profile derivation is not yet exercisable.

## Next bounded transition

`Delegate Mission Step 11 — Laboratorium commission acceptance disposition`

The occupied Alchemist independently accepts or refuses the exact custody-bound commission. Acceptance may make only its single Profile-derivation authority exercisable and must stop before derivation.

### Implementation status

Step 11 is implemented. See `docs/handoffs/delegate-mission-step-11-complete.md` and `contracts/delegate-mission-profile-derivation-commission-disposition.md`.

Acceptance stops at `DELEGATE_MISSION_PROFILE_DERIVATION_COMMISSION_ACCEPTED_PENDING_PROFILE_DERIVATION`. The Persona remains in Garrison custody and no Profile candidate exists yet.

## Next bounded transition

`Delegate Mission Step 12 — exact Delegate Profile-candidate derivation and return`

Laboratorium consumes the exact Step 11 derivation authority, derives one sealed candidate from the immutable scope, and returns it to Conscription. Derivation must grant no approval, installation, assembly, Seat binding, deployment, or operational authority.

### Implementation status

Step 12 is implemented. See `docs/handoffs/delegate-mission-step-12-complete.md` and `contracts/delegate-mission-profile-candidate-derivation-return.md`.

The route stops at `DELEGATE_MISSION_PROFILE_CANDIDATE_RETURNED_PENDING_CONSCRIPTION_INTAKE`. Only Conscription's exact candidate-intake disposition authority is open.

## Next bounded transition

`Delegate Mission Step 13 — Conscription Profile-candidate intake disposition`

The occupied Recruiter accepts or refuses the exact returned candidate after revalidating derivation, reservation, custody, and immutable scope lineage. Acceptance may prepare only the next examination handoff; it cannot approve or install the Profile.

### Implementation status

Step 13 is implemented. See `docs/handoffs/delegate-mission-step-13-complete.md` and `contracts/delegate-mission-profile-candidate-intake-disposition.md`.

Acceptance stops at `DELEGATE_MISSION_PROFILE_CANDIDATE_ACCEPTED_PENDING_EXAMINATION_PREPARATION`. Only one exact Conscription examination-preparation authority is open.

## Next bounded transition

`Delegate Mission Step 14 — examination-preparation handoff construction`

Conscription consumes the Step 13 authority and constructs one exact Senate-facing examination-preparation handoff with an examination-only assembly contract. It must stop before Senate intake or any Manifestation assembly.

### Implementation status

Step 14 is implemented. See `docs/handoffs/delegate-mission-step-14-complete.md` and `contracts/delegate-mission-examination-preparation-handoff.md`.

The route stops at `DELEGATE_MISSION_EXAMINATION_PREPARATION_HANDED_OFF_PENDING_SENATE_INTAKE`. Only the Lord Speaker's exact intake-disposition authority is open.

## Next bounded transition

`Delegate Mission Step 15 — Senate examination-preparation intake disposition`

The occupied Lord Speaker accepts or refuses the exact handoff. Acceptance may authorize only the next examination-only assembly preparation and must stop before any Manifestation is assembled.

### Implementation status

Step 15 is implemented. See `docs/handoffs/delegate-mission-step-15-complete.md` and `contracts/delegate-mission-examination-preparation-intake-disposition.md`.

Acceptance stops at `DELEGATE_MISSION_EXAMINATION_PREPARATION_ACCEPTED_PENDING_CONSCRIPTION_ASSEMBLY`. Only one exact examination-only assembly authority is open for Conscription.

## Next bounded transition

`Delegate Mission Step 16 — examination-only Manifestation assembly and Senate Stand delivery`

Conscription consumes the Step 15 authority, assembles the exact Persona, candidate Profile, and generic Officer v0 substrate solely for examination, and delivers the Manifestation to the Senate Stand. It must grant no mission Seat, deployment, operational-use, or execution authority.

### Implementation status

Step 16 is implemented. See `docs/handoffs/delegate-mission-step-16-complete.md` and `contracts/delegate-mission-examination-manifestation-assembly.md`.

The route stops at `DELEGATE_MISSION_EXAMINATION_MANIFESTATION_ASSEMBLED_DELIVERED_PENDING_SENATE_STAND_INTAKE`. Only the Bailiff's exact Stand intake-disposition authority is open.

## Next bounded transition

`Delegate Mission Step 17 — Senate Stand admission disposition`

The occupied Bailiff admits or refuses the exact examination-only Manifestation. Admission may open only bounded Senate examination authority and must preserve every operational prohibition.

### Implementation status

Step 17 is implemented. See `docs/handoffs/delegate-mission-step-17-complete.md` and `contracts/delegate-mission-examination-stand-admission.md`.

Admission stops at `DELEGATE_MISSION_EXAMINATION_MANIFESTATION_ADMITTED_SECURED_PENDING_EXAMINATION_OPENING`. Only the Lord Speaker's exact examination-opening authority is open; no hearing activity has occurred.

## Next bounded transition

`Delegate Mission Step 18 — bounded Senate Profile-examination opening`

The occupied Lord Speaker consumes the Step 17 authority and opens one exact bounded hearing contract. Opening must stop before any question, cognition, testimony, or finding.

### Implementation status

Step 18 is implemented. See `docs/handoffs/delegate-mission-step-18-complete.md` and `contracts/delegate-mission-profile-examination-opening.md`.

The route stops at `DELEGATE_MISSION_PROFILE_EXAMINATION_OPENED_PENDING_FIRST_QUESTION_COMMISSION`. Only the Lord Speaker's exact single-use authority to issue the first bounded question commission is open; no question has been authored or dispatched and no cognition has occurred.

## Next bounded transition

`Delegate Mission Step 19 — first jurisdiction-bound question commission`

The occupied Lord Speaker consumes the Step 18 authority to commission exactly one first question within the sealed trust jurisdiction and hearing limits. Commissioning must stop before question authorship, cognition, dispatch, or testimony.

## Non-negotiable terminal design

Before deployment becomes possible, the route must already define return, interruption, expiry, credential revocation, Seat unbinding, custody restoration, and Delegate termination. A successful mission does not leave a temporary Officer resident by accident.
