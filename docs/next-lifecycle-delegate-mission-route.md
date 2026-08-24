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

## Current route estimate

The original `14–18` transition estimate was invalidated by implementation. It understated identity-bound acceptance, examination authorship, dispatch, testimony, findings, operational activation, mission work, and terminal retirement boundaries.

At the current granularity, the complete route is expected to reach approximately Step `58–64`. This is an estimate, not a target. Before adding further repeated transitions, the route should be exercised locally and reviewed for safe consolidation that does not merge decision-makers or smuggle authority downstream.

Steps 1–69 are implemented. Step 69 terminates the temporary Delegate, unbinds its Seat, and restores Persona custody. The route is complete.

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

### Implementation status

Step 19 is implemented. See `docs/handoffs/delegate-mission-step-19-complete.md` and `contracts/delegate-mission-first-question-commission.md`.

The route stops at `DELEGATE_MISSION_FIRST_QUESTION_COMMISSION_ISSUED_PENDING_TRUST_SENATOR_ACCEPTANCE`. Only the exact recipient's single-use acceptance-or-refusal authority is open.

## Next bounded transition

`Delegate Mission Step 20 — trust Senator first-question commission disposition`

The exact occupied trust Senator independently accepts or refuses the sealed Step 19 commission. Acceptance may open only one bounded question-authorship authority; refusal opens nothing. Neither branch authors or dispatches a question.

### Implementation status

Step 20 is implemented. See `docs/handoffs/delegate-mission-step-20-complete.md` and `contracts/delegate-mission-first-question-commission-disposition.md`.

Acceptance stops at `DELEGATE_MISSION_FIRST_QUESTION_COMMISSION_ACCEPTED_PENDING_TRUST_QUESTION_AUTHORSHIP`; refusal stops at `DELEGATE_MISSION_FIRST_QUESTION_COMMISSION_REFUSED_NO_QUESTION_AUTHORITY`.

## Next bounded transition

`Delegate Mission Step 21 — bounded trust-question authorship`

The exact accepting trust Senator consumes the Step 20 authority to author and seal one question within the unchanged hearing contract. Authorship must stop before dispatch, testimony, or findings.

### Implementation status

Step 21 is implemented. See `docs/handoffs/delegate-mission-step-21-complete.md` and `contracts/delegate-mission-trust-question-authorship.md`.

The route stops at `DELEGATE_MISSION_TRUST_QUESTION_AUTHORED_SEALED_PENDING_DISPATCH_AUTHORIZATION`. Only the Lord Speaker's exact single-use dispatch-decision authority is open; the question has not been asked.

## Next bounded transition

`Delegate Mission Step 22 — trust-question dispatch authorization disposition`

The current exact Lord Speaker authorizes or refuses dispatch of the sealed Step 21 question. Authorization may open only one dispatch authority and must stop before the question is asked or testimony begins.

### Implementation status

Step 22 is implemented. See `docs/handoffs/delegate-mission-step-22-complete.md` and `contracts/delegate-mission-trust-question-dispatch-authorization.md`.

Authorization stops at `DELEGATE_MISSION_TRUST_QUESTION_DISPATCH_AUTHORIZED_PENDING_BAILIFF_DISPATCH`; refusal opens no testimony path.

## Next bounded transition

`Delegate Mission Step 23 — secured unchanged trust-question dispatch`

The exact identity-bound Bailiff consumes the Step 22 authority and dispatches the sealed question unchanged. Dispatch may open only one bounded response authority and must stop before testimony cognition.

### Implementation status

Step 23 is implemented. See `docs/handoffs/delegate-mission-step-23-complete.md` and `contracts/delegate-mission-trust-question-dispatch.md`.

The route stops at `DELEGATE_MISSION_TRUST_QUESTION_DISPATCHED_UNCHANGED_PENDING_TESTIMONY_RESPONSE`.

## Next bounded transition

`Delegate Mission Step 24 — sealed bounded trust response`

The examination-only Manifestation consumes the exact response authority and returns one structured answer with evidence claims, refusals, and uncertainties. No finding authority may open merely from one response.

### Implementation status

Step 24 is implemented. See `docs/handoffs/delegate-mission-step-24-complete.md`, `docs/handoffs/delegate-mission-first-trust-question-leg-complete.md`, and `contracts/delegate-mission-trust-testimony-response.md`.

The trust-question leg stops at `DELEGATE_MISSION_TRUST_TESTIMONY_RESPONSE_SEALED_PENDING_SECURITY_QUESTION_COMMISSION`. No finding authority exists.

## Next bounded transition

`Delegate Mission Step 25 — security-question commission issuance`

The Lord Speaker consumes the exact Step 24 authority to issue one bounded commission to the exact occupied security Senator. It must stop before recipient acceptance or question authorship.

### Implementation status

Step 25 is implemented. See `docs/handoffs/delegate-mission-step-25-complete.md` and `contracts/delegate-mission-security-question-commission.md`.

The route stops at `DELEGATE_MISSION_SECURITY_QUESTION_COMMISSION_ISSUED_PENDING_SECURITY_SENATOR_ACCEPTANCE`. Only the exact recipient's single-use acceptance-or-refusal authority is open.

## Next bounded transition

`Delegate Mission Step 26 — security Senator commission disposition`

The exact occupied security Senator independently accepts or refuses the sealed Step 25 commission. Acceptance may open only one bounded security-question authorship authority and must stop before cognition or authorship.

### Implementation status

Steps 26–30 are implemented. See `docs/handoffs/delegate-mission-steps-26-30-complete.md`, `docs/handoffs/delegate-mission-security-question-leg-complete.md`, and the corresponding security-question contracts.

The accepted route authors one bounded security question, obtains a separate Lord Speaker dispatch decision, dispatches unchanged through the exact Bailiff, and seals one structured response. The leg stops at `DELEGATE_MISSION_SECURITY_TESTIMONY_RESPONSE_SEALED_PENDING_USABILITY_QUESTION_COMMISSION`.

## Next bounded transition

`Delegate Mission Step 31 — usability-question commission issuance`

The Lord Speaker consumes the exact Step 30 authority to issue one bounded commission to the exact occupied usability Senator. It must stop before recipient acceptance or question authorship.

### Implementation status

Steps 31–36 are implemented. See `docs/handoffs/delegate-mission-steps-31-36-complete.md`, `docs/handoffs/delegate-mission-usability-question-leg-complete.md`, and the corresponding usability-question contracts.

The route stops at `DELEGATE_MISSION_USABILITY_TESTIMONY_RESPONSE_SEALED_PENDING_FINDING_AUTHORITY_OPENING`. All three jurisdictional responses are sealed, but no finding authority is yet open.

## Next bounded transition

`Delegate Mission Step 37 — three-jurisdiction finding-authority opening`

The Lord Speaker consumes the exact Step 36 authority only after independently revalidating the three testimony turns, exact jurisdictional occupancies, hearing contract, custody, identity baseline, and shared defect-attribution rubric. The transition may open one separate finding authority for each exact Senator and must stop before any finding cognition or authorship.

### Implementation status

Steps 37–38 are implemented. See `docs/handoffs/delegate-mission-steps-37-38-complete.md`, `docs/handoffs/delegate-mission-independent-findings-leg-complete.md`, and the corresponding finding contracts.

The route stops at `DELEGATE_MISSION_SENATOR_FINDINGS_SEALED_PENDING_DELIBERATION_OPENING`. The three findings remain independent and unchanged; only one Lord Speaker deliberation-opening authority exists.

## Next bounded transition

`Delegate Mission Step 39 — deliberation opening`

The Lord Speaker admits all three sealed findings unchanged into one bounded deliberation record. It must preserve disagreement, severity, attribution, limitations, uncertainty, and the mandatory Security blocking condition, and stop before reconciliation cognition.

### Implementation status

Steps 39–40 are implemented. See `docs/handoffs/delegate-mission-steps-39-40-complete.md`, `docs/handoffs/delegate-mission-deliberation-reconciliation-leg-complete.md`, and the corresponding contracts.

The route stops at `DELEGATE_MISSION_FINDINGS_RECONCILED_PENDING_DISPOSITION_AUTHORITY_OPENING`. Reconciliation preserves the three findings and Security block without voting or aggregation. No Senate disposition authority is yet open.

## Next bounded transition

`Delegate Mission Step 41 — disposition-authority opening`

The exact Lord Speaker consumes the Step 40 authority to open exactly one Senate-disposition authority. It must stop before disposition cognition or authorship.

### Implementation status

Steps 41–42 are implemented. See `docs/handoffs/delegate-mission-steps-41-42-complete.md`, `docs/handoffs/delegate-mission-senate-disposition-leg-complete.md`, and the corresponding contracts.

The route stops at `DELEGATE_MISSION_SENATE_DISPOSITION_SEALED_PENDING_IMPERATOR_PROFILE_APPROVAL`. The Security block is mechanically binding, and the Senate disposition grants no Imperator or operational authority.

## Next bounded transition

`Delegate Mission Step 43 — Imperator Profile-approval decision`

Imperator independently decides the exact Senate disposition and sealed Delegate Profile candidate. Senate disposition is evidence, not sovereign approval; Step 43 must stop before operational qualification or installation.

### Implementation status

Step 43 is implemented. See `docs/handoffs/delegate-mission-step-43-complete.md` and `contracts/delegate-mission-profile-approval-decision.md`.

The approved route stops at `DELEGATE_MISSION_PROFILE_APPROVED_PENDING_CONSCRIPTION_OPERATIONAL_QUALIFICATION`. Only one exact request authority for Conscription exists; no operational Profile, Manifestation, mission Seat, deployment, or execution authority exists.

## Next bounded transition

`Delegate Mission Step 44 — Conscription operational qualification`

The exact occupied Recruiter consumes the Step 43 request and qualifies the approved Profile against the immutable mission scope, live custody, generic Officer v0 substrate, intended mission Seat, resource limits, stop conditions, and return design. It must stop before operational Manifestation assembly.

### Implementation status

Steps 44–46 are implemented. See `docs/handoffs/delegate-mission-operational-construction-leg-complete.md` and `contracts/delegate-mission-operational-construction.md`.

The route stops at `DELEGATE_MISSION_MANIFESTATION_BOUND_PENDING_DEPLOYMENT_AUTHORIZATION`. The generation-1 binding is exact and inert; no deployment, custody-transfer, operational-use, resource, external-action, or execution authority exists.

## Next bounded transition

`Delegate Mission Step 47 — Seneschal deployment-authorization decision`

The exact occupied Seneschal decides whether the bound Delegate may proceed toward deployment. Authorization remains distinct from the Constable's later custody transition and from operational execution.

### Implementation status

Steps 47–48 are implemented. See `docs/handoffs/delegate-mission-deployment-leg-complete.md` and `contracts/delegate-mission-deployment-and-custody-transition.md`.

The route stops at `DELEGATE_MISSION_DEPLOYED_CUSTODY_TRANSITIONED_PENDING_MISSION_ACTIVATION`. Deployment is complete and custody is unavailable elsewhere, but no operational-use, cognition, provider, resource, perimeter, external-action, or execution authority exists.

## Next bounded transition

`Delegate Mission Step 49 — bounded mission activation`

Activation must prove the exact binding and deployed custody are current, then open only the next bounded mission-control authority. It must not imply cognition, provider invocation, or resource access.

### Implementation status

Step 49 is implemented. See `docs/handoffs/delegate-mission-step-49-complete.md` and `contracts/delegate-mission-runtime-activation.md`.

The route stops at `DELEGATE_MISSION_RUNTIME_ACTIVE_PENDING_MISSION_CONTROL_INTAKE`. One exact single-use Seneschal mission-control intake authority is open; cognition, provider invocation, data, tools, credentials, perimeter crossing, external action, execution, and continuing turns remain unauthorized.

## Next bounded transition

`Delegate Mission Step 50 — Seneschal mission-control intake disposition`

The exact occupied Seneschal must accept or refuse the active Delegate and its unchanged mission-use contract before any bounded cognition commission can be constructed.

### Implementation status

Step 50 is implemented. See `docs/handoffs/delegate-mission-step-50-complete.md` and `contracts/delegate-mission-control-intake.md`.

The accepted route stops at `DELEGATE_MISSION_CONTROL_ACCEPTED_PENDING_BOUNDED_COGNITION_COMMISSION_CONSTRUCTION`. Refusal, return, and deferral open no next authority. Acceptance opens only one exact single-use Seneschal commission-construction authority.

## Next bounded transition

`Delegate Mission Step 51 — bounded cognition commission construction`

The exact occupied Seneschal may construct one cognition-only mission commission from the unchanged mission use. Construction must not invoke a provider, release data or tools, authorize external action, or create continuing-turn authority.

### Implementation status

Step 51 is implemented. See `docs/handoffs/delegate-mission-step-51-complete.md` and `contracts/delegate-mission-bounded-cognition-commission.md`.

The route stops at `DELEGATE_MISSION_BOUNDED_COGNITION_COMMISSION_CONSTRUCTED_PENDING_RESOURCE_AND_INVOCATION_AUTHORIZATION`. The commission is sealed and single-iteration, but cognition and every resource or action authority remain false.

## Next bounded transition

`Delegate Mission Step 52 — resource and invocation readiness assessment`

Curia mechanically preserves the commission's exact resource requirements and determines whether an exact model binding exists before any authorization request can be competent. It must not select a model, release a resource, or invoke cognition.

### Implementation status

Step 52 is implemented. See `docs/handoffs/delegate-mission-step-52-complete.md` and `contracts/delegate-mission-resource-invocation-readiness.md`.

The assessment correctly stops at `DELEGATE_MISSION_RESOURCE_REQUIREMENTS_ASSESSED_PENDING_ORACLE_MODEL_REQUIREMENT_COMMISSION` because the Delegate chain contains no exact model binding. It opens only one Oracle model-requirement commission authority; resource and invocation authorization remain unavailable.

## Next bounded transition

`Delegate Mission Step 53 — model-requirement criteria presentation`

The exact occupied Seneschal presents explicit selection policy values for Imperator decision because they cannot be inferred from the immutable turn contract.

### Implementation status

Steps 53–55 are implemented. See `docs/handoffs/delegate-mission-model-criteria-commission-leg-complete.md` and `contracts/delegate-mission-model-criteria-commission.md`.

The route stops at Oracle inbox checkpoint `ISSUED_PENDING_ORACLE_ACCEPTANCE`. The commission binds the authorized criteria, pinned catalogue snapshot, Delegate readiness, bounded turn, custody, and Seat lineage. No Oracle evaluation or model authority exists.

## Next bounded transition

`Delegate Mission Step 56 — exact Augur commission acceptance`

The occupied Augur may accept the unchanged commission against the still-current pinned snapshot. Acceptance opens only Oracle evaluation-case construction.

### Implementation status

Steps 56–57 are implemented by the existing governed Oracle acceptance and evaluation-case services. See `docs/handoffs/delegate-mission-oracle-intake-leg-complete.md` and `contracts/delegate-mission-oracle-intake-evaluation.md`.

The route stops at `ORACLE_MODEL_EVALUATION_CASE_OPENED_PENDING_AUGUR_ELIGIBILITY_FINDINGS`. Candidate inclusion is frozen from the pinned catalogue snapshot and authorized criteria; no recommendation, selection, assignment, or invocation authority exists.

## Next bounded transition

`Delegate Mission Step 58 — evidence-bound Oracle eligibility findings`

The Augur must issue one independent finding for every frozen candidate authority. Completion may open comparative assessment only when at least one candidate is eligible.

### Implementation status

Steps 58–60 are implemented by the existing governed Oracle finding, comparison, and recommendation services. See `docs/handoffs/delegate-mission-oracle-judgment-leg-complete.md` and `contracts/delegate-mission-oracle-judgment.md`.

The route stops at `ORACLE_MODEL_RECOMMENDATION_SEALED_PENDING_CURIA_SELECTION_DECISION`. Oracle's recommendation is attributable and evidence-bound, but it is not a selection, assignment, or invocation.

## Next bounded transition

`Delegate Mission Step 61 — Curia exact model-selection decision`

The occupied Seneschal may select only an eligible model identified by the frozen Oracle route, reject all candidates, or return a new commission. Selection must remain distinct from binding, access attestation, and invocation authorization.

### Implementation status

Step 61 is implemented. See `docs/handoffs/delegate-mission-step-61-complete.md` and `contracts/delegate-mission-model-selection.md`.

The selected route stops at `DELEGATE_MISSION_MODEL_SELECTED_PENDING_CONSCRIPTION_BINDING_SEAL`. Only one exact single-use Conscription model-binding sealing authority is open; assignment, access, invocation, resources, and execution remain unauthorized.

## Next bounded transition

`Delegate Mission Step 62 — exact Delegate turn model-binding seal`

Conscription must seal the selected provider/model version and configuration to the exact Delegate Manifestation, Seat, bounded commission, and turn sequence without mutating the approved Profile or granting access.

### Implementation status

Step 62 is implemented. See `docs/handoffs/delegate-mission-step-62-complete.md` and `contracts/delegate-mission-model-binding.md`.

The route stops at `DELEGATE_MISSION_MODEL_BINDING_SEALED_PENDING_ACCESS_ATTESTATION`. Only one exact single-use Clavium access-attestation authority is open; assignment, access, credentials, invocation, resources, and execution remain unauthorized.

## Next bounded transition

`Delegate Mission Step 63 — exact bound-model access attestation`

Clavium must attest whether access to the exact bound provider/model version is available, under which restrictions, and until what expiry. Attestation must not release credentials or invoke the provider.

### Implementation status

Steps 63–64 are implemented. See `docs/handoffs/delegate-mission-steps-63-64-complete.md` and `contracts/delegate-mission-access-and-authorization.md`.

The route stops at `DELEGATE_MISSION_RESOURCE_AND_INVOCATION_AUTHORIZED_PENDING_SCOPED_ACTIVATION`. Only one exact Clavium activation authority is open; no credential, invocation, resource, external action, or execution has occurred.

## Next bounded transition

`Delegate Mission Step 65 — scoped provider-invocation activation`

Clavium may create one expiring, single-use credential lease and activation for the exact binding and turn. Secret material must remain undisclosed and possession must not transfer.

### Implementation status

Step 65 is implemented. See `docs/handoffs/delegate-mission-step-65-complete.md`.

The route stops at `DELEGATE_MISSION_PROVIDER_INVOCATION_ACTIVATED_PENDING_ONE_BOUNDED_COGNITION_TURN`. Step 66 must exercise that exact authority through a governed cognition gateway and consume the lease atomically.

## Next bounded transition

`Delegate Mission Step 66 — one governed provider invocation and bounded cognition turn`

Citadel revalidates the exact commission, binding, access attestation, runtime mapping, target, lease, and single-turn authority before invoking the explicitly named Symfony AI platform and runtime model. Catalogue identity is never passed to the provider as the executable model name.

### Implementation status

Step 66 is implemented. See `docs/handoffs/delegate-mission-step-66-complete.md` and `contracts/delegate-mission-bounded-cognition-turn.md`.

The route stops at `DELEGATE_MISSION_BOUNDED_COGNITION_TURN_COMPLETE_PENDING_CURIA_DISPOSITION`. Step 67 must dispose the exact sealed result and open only its governed return path.

## Terminal transitions

`Delegate Mission Steps 67–69 — result disposition, return authorization, and terminal retirement`

Curia disposes the exact bounded result without inventing continuation, then separately authorizes the predeclared return contract. Garrison consumes that authority to restore Persona custody, unbind the mission Seat, and retire the temporary Manifestation.

### Implementation status

Steps 67–69 are implemented. See `docs/handoffs/delegate-mission-route-complete.md` and `contracts/delegate-mission-result-return-and-retirement.md`.

The terminal checkpoint is `DELEGATE_MISSION_RETURNED_UNBOUND_CUSTODY_RESTORED_RETIRED_TERMINAL`. No authority survives for continuation, redeployment, or reuse; another mission requires a fresh Delegate lifecycle.

## Non-negotiable terminal design

Before deployment becomes possible, the route must already define return, interruption, expiry, credential revocation, Seat unbinding, custody restoration, and Delegate termination. A successful mission does not leave a temporary Officer resident by accident.
