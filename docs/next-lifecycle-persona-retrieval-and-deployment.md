# Next lifecycle: Persona retrieval and deployment

## Starting checkpoint

Persona production is complete. The canonical 33-step route ends with the exact Persona admitted into Garrison as `ADMITTED_HELD`. Production, Senate confirmation, Guildhall fulfillment, and Garrison admission are implemented and covered by the cumulative acceptance test.

## Governing separation

The next lifecycle must not extend production by implication. It begins only after a new, explicit personnel-use demand.

The central boundary is:

**An admitted Persona is stored cognitive identity—not an operative, manifestation, Seat occupant, or authorization to work.**

## Canonical downstream route

**Curia capability demand → Guildhall profession and Persona resolution → Imperator personnel-use authorization → Garrison reservation → Imperator Profile-derivation authorization → Conscription acceptance → Constable custody-bound derivation lease → Conscription commission → Alchemist acceptance → Laboratorium Profile derivation and return → Conscription examination assembly → Senate examination and disposition → Imperator Profile approval → Conscription operational qualification → authorized deployment → governed return or retirement**

The route is implemented through the sealed Profile-examination disposition. Imperator Profile approval, operational qualification and assembly, Seat binding, deployment authorization, first bounded execution, and governed return or retirement remain downstream work.

## Enumerated downstream flow

1. **Curia states the mission demand in capabilities.** Curia supplies skills, attributes, constraints, tools, data needs, and expected outcomes. It does not select a profession or Persona.
2. **Guildhall resolves profession and exact Persona.** Guildhall alone translates the capability demand into a profession and determines suitability against Garrison inventory facts.
3. **Curia presents the identity-bearing personnel-use request.** Curia shows Imperator the functional demand together with Guildhall's exact profession, Persona, suitability determination, custody identity, and digests without amending them.
4. **Imperator decides personnel use.** Only `AUTHORIZED` grants personnel-use authority; refusal, revision, alternative, clarification, and deferral remain non-authorizing.
5. **Guildhall accepts the exact act and requests reservation.** Acceptance preserves the authorized identity and permits one exact reservation request; Guildhall cannot reserve or retrieve the Persona.
6. **The Constable decides reservation.** Garrison verifies admission, availability, identity, instance, and reservation conflicts. Success stops at `RESERVED_PENDING_PROFILE_DERIVATION_AUTHORIZATION` while custody remains held.
7. **Curia constructs the immutable Profile scope.** Curia binds the successful reservation to the exact structured Mission Plan and identifies Curia as steward, Conscription as commissioner and installer, Laboratorium as transformer, Senate as examiner, and Imperator as approver.
8. **Imperator decides Profile derivation.** Only `AUTHORIZED` reaches `PROFILE_DERIVATION_AUTHORIZED_PENDING_CONSCRIPTION_ACCEPTANCE`; no retrieval, Profile, or manifestation exists.
9. **The Recruiter accepts and requests a derivation handoff.** Conscription revalidates the entire chain and issues one custody-bound, derivation-only request to the Constable.
10. **The Constable decides the derivation lease.** Approval reaches `PROFILE_DERIVATION_HANDOFF_APPROVED_PENDING_CONSCRIPTION_LABORATORIUM_COMMISSION`; refusal grants nothing. Garrison retains custody at `ADMITTED_HELD`.
11. **The Recruiter commissions Laboratorium.** Conscription issues one sealed `DERIVE_ONE_EXACT_MISSION_PROFILE` commission to `laboratorium.alchemist`. Its authority remains non-exercisable pending recipient acceptance.
12. **The Alchemist accepts the exact commission.** The occupied Alchemist independently validates the commission, lease, Persona, Profile scope, return destination, active occupancy, and digests. Acceptance reaches `PROFILE_DERIVATION_COMMISSION_ACCEPTED_PENDING_PROFILE_DERIVATION` and makes authority exercisable for one candidate only.
13. **The Alchemist elaborates; Laboratorium seals the Profile candidate.** The occupied Alchemist cognitively elaborates the mission-specific operating posture, responsibilities, reasoning priorities, evidence discipline, tool-use behavior, input/output contracts, escalation, uncertainty, failure behavior, and Persona adaptations. Laboratorium machinery independently validates that elaboration against the accepted commission, active occupancy, exact Persona, immutable Profile scope, live custody lease, limitations, return destination, and source digests; only then does it version and seal the exact candidate. The candidate reaches `PROFILE_CANDIDATE_DERIVED_VERSIONED_SEALED_PENDING_RETURN_TO_CONSCRIPTION` without being returned or gaining downstream authority.
14. **Laboratorium returns the exact sealed candidate.** Laboratorium revalidates the candidate, active Alchemist occupancy, live `ADMITTED_HELD` custody lease, immutable scope, authorization limitations, complete lineage, and exact `conscription.recruiter` destination. It consumes the candidate's one-use return authority and issues one sealed return record at `PROFILE_CANDIDATE_RETURNED_PENDING_CONSCRIPTION_ACCEPTANCE`. Delivery grants Conscription no acceptance, approval, installation, examination-assembly, Senate-examination, custody-release, deployment, or execution authority.
15. **The Recruiter accepts the exact returned candidate.** The occupied ordinary Recruiter independently revalidates the immutable return packet, candidate digest and seal, Persona identity, mission scope, lineage, and live Garrison custody lease. It consumes only the candidate-acceptance authority and records `PROFILE_CANDIDATE_ACCEPTED_PENDING_EXAMINATION_ASSEMBLY_AUTHORIZATION`. Acceptance grants no approval, installation, examination-assembly, Senate-examination, custody-release, deployment, or execution authority.
16. **The Recruiter requests examination-assembly authority.** Conscription submits one sealed request to `senate.lord-speaker`, bound to the accepted candidate, exact Persona, version-0 generic Officer substrate, live lease, complete lineage, examination-only purpose, `senate.stand` target, and Conscription return destination. The request reaches `EXAMINATION_ASSEMBLY_AUTHORIZATION_REQUESTED_PENDING_SENATE_INTAKE`; Senate has not accepted it and no assembly authority exists.
17. **The Lord Speaker decides Senate intake.** The occupied Lord Speaker revalidates the exact request, candidate, acceptance, scope, Persona, live custody, substrate, and source lineage, then records `ACCEPTED` or `REFUSED` with rationale. Refusal reaches `EXAMINATION_ASSEMBLY_REFUSED_NO_AUTHORITY`. Acceptance reaches `EXAMINATION_ASSEMBLY_AUTHORIZED_PENDING_CONSCRIPTION_ASSEMBLY` and grants one-use examination-only installation and assembly authorities; general Profile installation and all later authorities remain false.
18. **Conscription assembles the examination Manifestation.** The occupied Recruiter mechanically consumes both examination-specific authorities and installs the complete sealed candidate content and scope—not merely its identity metadata—into generic Officer substrate version 0. This gives Senate the substantive operating posture, directives, contracts, limitations, and failure behavior it must examine while the sealed Manifestation remains examination-only. It is delivered to the Bailiff's stand intake at `EXAMINATION_MANIFESTATION_ASSEMBLED_DELIVERED_PENDING_SENATE_STAND_INTAKE`; no operational installation, stand admission, examination, approval, deployment, or execution occurs.
19. **The Bailiff admits and secures the exact Manifestation.** The occupied Bailiff revalidates the sealed delivery, non-operational restrictions, live custody, consumed authorities, and lineage, then admits the subject to `senate.stand` at `EXAMINATION_MANIFESTATION_ADMITTED_SECURED_PENDING_SENATE_EXAMINATION_OPENING`. Examination authority remains false.
20. **The Lord Speaker opens the Profile examination.** The secured admission becomes one sealed case with Trust, Security, and Usability commissions and an explicit defect-attribution rubric. The route stops at `PROFILE_EXAMINATION_OPENED_PENDING_SENATOR_ACCEPTANCE`; no Senator authority is exercisable and testimony remains closed.
21. **The canonical panel accepts.** Each occupied Senator validates and accepts the exact commission. Once Trust, Security, and Usability have all accepted, Senate seals `PROFILE_EXAMINATION_PANEL_ACCEPTED_PENDING_TESTIMONY_OPENING`; question and finding authorities remain non-exercisable until testimony is separately opened.
22. **The Lord Speaker opens testimony.** The occupied Lord Speaker revalidates the exact case and all three sealed commission acceptances, consumes one testimony-opening authority, and seals `PROFILE_EXAMINATION_TESTIMONY_OPENED_PENDING_SENATOR_QUESTIONING`. Each accepted Senator's bounded question authority becomes exercisable; finding authority and deliberation remain closed, and no question or testimony turn occurs in this step.
23. **Each Senator independently seals one bounded question.** Trust, Security, and Usability each exercise only their own accepted commission through a distinct cognition surface. Every record preserves the exact case, examination-only Manifestation, Profile candidate and Persona identity, custody lease, commission, acceptance, jurisdiction, lineage, rubric, and Conscription return destination. The route stops at `PROFILE_EXAMINATION_QUESTION_AUTHORED_SEALED_PENDING_DISPATCH`; no question is dispatched, no answer or finding exists, and deliberation remains closed.
24. **Senate seals the attributable testimony baseline.** Each exact sealed question is dispatched unchanged to the exact examination-only Manifestation through its distinct tool-less witness cognition surface. Every answer is sealed with its question, jurisdiction, Senator, case, Manifestation, Profile candidate, Persona identity, custody lease, rubric, lineage, and return destination. Once all three turns exist, Senate seals `PROFILE_EXAMINATION_TESTIMONY_ANSWERS_SEALED_PENDING_FINDING_AUTHORITY_OPENING`; finding authority and deliberation remain closed.
25. **The Lord Speaker opens the finding phase.** The complete sealed testimony baseline, live custody, exact case, accepted commissions, current Senator occupancies, shared rubric, and common identity baseline are revalidated. One bounded finding authority is opened for each exact Senator at `PROFILE_EXAMINATION_FINDING_AUTHORITIES_OPENED_PENDING_SENATOR_FINDINGS`; deliberation remains closed.
26. **Each Senator independently seals one finding.** Trust, Security, and Usability each consume only their own jurisdictional authority and evidence. After all three exact findings exist, Senate seals `PROFILE_EXAMINATION_SENATOR_FINDINGS_SEALED_PENDING_DELIBERATION_OPENING` without comparison, voting, aggregation, reconciliation, or disposition.
27. **The Lord Speaker opens deliberation.** Senate revalidates the readiness seal, all three findings, their authority opening, the exact case, live custody, and current Lord Speaker occupancy. The findings are admitted unchanged at `PROFILE_EXAMINATION_DELIBERATION_OPENED_PENDING_RECONCILIATION`; bounded reconciliation authority is exercisable, but voting, aggregation, reconciliation itself, and disposition remain closed.

28. **Reconcile the sealed findings.** The Lord Speaker explains agreement, disagreement, defect attribution, severity, limitations, and uncertainty without modifying a finding, voting, averaging, or suppressing dissent. Senate seals `PROFILE_EXAMINATION_FINDINGS_RECONCILED_PENDING_DISPOSITION_AUTHORITY_OPENING`; disposition authority remains closed.

29. **Open Senate disposition authority.** Revalidate the exact reconciliation and complete admitted finding set, then grant one bounded disposition authority at `PROFILE_EXAMINATION_DISPOSITION_AUTHORITY_OPENED_PENDING_LORD_SPEAKER_DISPOSITION`. Stop before a disposition is authored.

30. **Seal the Senate disposition.** Issue one attributable Profile-examination disposition bound to every finding and the reconciliation at `PROFILE_EXAMINATION_DISPOSITION_SEALED_PENDING_IMPERATOR_PROFILE_APPROVAL`. Preserve refusal and revision branches; grant no Imperator or operational authority.

### Proposed completion batches

31. **Imperator decides Profile approval.** Present the exact candidate and complete Senate record. Only explicit approval of a Senate `APPROVED` disposition authorizes the next operational-qualification request at `IMPERATOR_PROFILE_APPROVED_PENDING_CONSCRIPTION_OPERATIONAL_QUALIFICATION`; refusal, revision, clarification, alternative, and deferral are sealed but non-authorizing. Approval grants no installation, assembly, Seat-binding, deployment, or execution authority.
32. **Conscription installs and qualifies the approved Profile operationally.** The occupied ordinary Recruiter consumes the exact qualification-request authority and revalidates the Imperator approval, Senate disposition, Persona, live custody lease, Profile candidate, mission scope, tools, data, stop conditions, and both return representations. The intended Seat is derived—not selected—from Curia's exact capability slot, and compatibility is limited to the identity- and authority-neutral generic Officer version-0 contract. The sealed boundary is `PROFILE_OPERATIONALLY_QUALIFIED_PENDING_MANIFESTATION_ASSEMBLY`: one Manifestation-assembly authority is exercisable, while Seat binding, deployment, custody transfer, and execution remain closed.
33. **Conscription assembles the operational Manifestation.** Consume the exact qualification's assembly authority and mechanically combine the custody-bound Persona, qualified operational Profile, and identity- and authority-neutral generic Officer version-0 substrate. Seal `OPERATIONAL_MANIFESTATION_ASSEMBLED_PENDING_SEAT_BINDING` with one bounded Seat-binding authority, while the Manifestation remains unbound and prohibited from operational use. No tool access, credentials, external action, deployment, custody transfer, or execution authority exists.
34. **Bind the exact Manifestation to its intended Seat.** Conscription consumes the assembly's exact Seat-binding authority, revalidates live custody and the complete qualification/assembly lineage, proves the pre-derived Seat is empty, and commits one atomic generation-1 occupancy with no predecessor or implicit supersession. The boundary is `OPERATIONAL_MANIFESTATION_BOUND_PENDING_DEPLOYMENT_AUTHORIZATION`. Binding grants no operational use, tool access, credentials, external action, deployment, custody transfer, or execution authority.
35. **Authorize deployment and transfer bounded operational custody.** The occupied Seneschal authorizes the exact bound Manifestation for one immutable mission-use contract. The occupied Constable independently consumes that authorization, marks the Persona unavailable at `DEPLOYED_BOUND`, and records the exact operational custodian at `OPERATIONAL_MANIFESTATION_DEPLOYED_CUSTODY_TRANSITIONED_PENDING_BOUNDED_EXECUTION`. Curia gains no custody or execution authority; Garrison gains no selection or execution authority; operational use, tools, credentials, external action, and execution remain closed.
36. **Execute one bounded smoke iteration.** The occupied Seneschal seals one exact HTTPS input and one-use execution authority against the deployed custody record. The bound Manifestation consumes it for one internal cognition iteration, produces one attributable output, evaluates its stop conditions, and stops at `OPERATIONAL_MANIFESTATION_BOUNDED_EXECUTION_COMPLETED_PENDING_RETURN`. No tool, credential, network, external action, undeclared data, or continuing execution authority exists.
37. **Return, retire, or restore custody.** Consume the one-use operational authority, unbind or retire the Manifestation as required, record disposition and lineage, and restore or supersede the Persona/Profile custody state deterministically.

## Current implementation checkpoint

The implemented canonical route ends after step 36 at `OPERATIONAL_MANIFESTATION_BOUNDED_EXECUTION_COMPLETED_PENDING_RETURN`; refusal remains a sealed alternate terminal branch at `EXAMINATION_ASSEMBLY_REFUSED_NO_AUTHORITY`, and every non-approving Imperator disposition is recorded without downstream authority. Step 37 is the one remaining batch through deterministic return and custody restoration.

- Garrison has changed the exact Persona custody fact from available `ADMITTED_HELD` to unavailable `DEPLOYED_BOUND` and recorded the bound mission Manifestation as operational custodian.
- The commission and its acceptance have been consumed only to derive one deterministic, immutable Profile candidate at version 1.
- The candidate preserves the exact Persona identity, immutable mission scope, limitations, custody lease, complete source lineage, and required return destination.
- The exact sealed candidate has been returned to Conscription and independently accepted by the occupied ordinary Recruiter.
- The exact examination-only Manifestation is secured on the Senate stand under active Bailiff proceeding security.
- The Lord Speaker has opened the Profile examination, and Trust, Security, and Usability have accepted their exact commissions.
- Trust, Security, and Usability have independently authored questions, received sealed testimony, and sealed one exact attributable finding each.
- The Lord Speaker has admitted the three findings unchanged and consumed one deliberation-opening authority.
- The Lord Speaker has reconciled the three exact findings without modifying them, voting, aggregation, or suppressed dissent.
- Senate has sealed an `APPROVED` disposition bound to every exact finding and the reconciliation.
- The Imperator has explicitly approved that exact examined Profile, and Conscription has consumed the resulting qualification-request authority.
- Conscription has installed and qualified the exact operational Profile for the Seat derived from Curia's immutable capability slot.
- Conscription has consumed the exact Seat-binding authority and atomically bound the Manifestation to its pre-derived Seat at occupancy generation 1.
- The occupied Seneschal has authorized one exact immutable mission use, and the occupied Constable has consumed the deployment and custody-transition authority. Operational use, tool access, credentials, external action, supersession, and execution remain closed.
- The occupied Seneschal has sealed one exact input authorization, and the bound Manifestation has consumed it for one attributable internal cognition iteration. No continuing execution authority exists; return, retirement, unbinding, and custody restoration remain pending.

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

## Capability-to-profession question — resolved

The initiating mission artifact carries capability requirements rather than profession or Persona selections. Guildhall owns capability-to-profession translation and determines Persona suitability against Garrison facts. Personnel-use authorization, reservation, and the custody-bound derivation lease are now separately implemented and cannot be inferred from suitability.

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

Acceptance permits one exact request to the occupied Constable for a custody-bound, derivation-only Persona handoff. The request preserves the complete authority chain and stops at `PENDING_CONSTABLE_PROFILE_DERIVATION_HANDOFF_DISPOSITION`. It grants no handoff, retrieval, custody release, Laboratorium commission, Profile artifact, manifestation assembly, Senate examination, Seat binding, deployment, or execution authority. Garrison independently decides the handoff in the following transition.

## Constable derivation-handoff disposition — implemented

The occupied Constable independently validates the exact Conscription request, acceptance, Imperator act, successful reservation, immutable Profile scope, admitted Persona custody record, availability, instance, and complete digest chain. The Constable may record `APPROVED` or `REFUSED` with an attributable rationale.

Approval creates only a custody-bound Profile-derivation lease and authority for Conscription to issue the next exact Laboratorium-commission request. Garrison retains the Persona in `ADMITTED_HELD`; there is no unrestricted retrieval or custody release. Refusal creates no downstream authority. The implementation stops at `PROFILE_DERIVATION_HANDOFF_APPROVED_PENDING_CONSCRIPTION_LABORATORIUM_COMMISSION`, before any Laboratorium commission, Profile artifact, manifestation assembly, Senate examination, Seat binding, deployment, or execution.

## Conscription Profile-derivation commission — implemented

The occupied ordinary Recruiter may commission Laboratorium only from an exact approved Constable handoff disposition. Conscription revalidates the complete disposition, handoff request, acceptance, reservation, custody record, immutable Profile scope, Persona identity, instance, and digest chain before issuing one commission to `laboratorium.alchemist`.

The commission is limited to `DERIVE_ONE_EXACT_MISSION_PROFILE`, preserves Garrison custody at `ADMITTED_HELD`, and requires return to Conscription. It carries Profile-derivation authority but that authority is not exercisable until the occupied Alchemist accepts the exact commission. The implementation stops at `PENDING_ALCHEMIST_PROFILE_DERIVATION_COMMISSION_ACCEPTANCE`; no Profile artifact, approval, installation, manifestation assembly, Senate examination, Seat binding, deployment, or execution exists.

## Alchemist commission acceptance — implemented

The occupied Alchemist independently validates the exact commission, approved Constable lease, immutable Persona, custody lease, Profile scope, return destination, instance, active Laboratorium occupancy, and complete digest chain. Only that occupied Seat may accept the commission.

Acceptance changes the exact commission's Profile-derivation authority from non-exercisable to exercisable and grants authority to create one derived Profile candidate. It does not itself derive or create the candidate. The implementation stops at `PROFILE_DERIVATION_COMMISSION_ACCEPTED_PENDING_PROFILE_DERIVATION`; Garrison custody remains `ADMITTED_HELD`, and no Profile artifact, approval, installation, manifestation, Senate examination, Seat binding, deployment, or execution exists.

## Laboratorium Profile-candidate derivation — implemented

The occupied Alchemist uses only the exact accepted commission and Imperator limitations to cognitively elaborate the mission-specific Profile content. Laboratorium machinery—not the model—validates the elaboration contract, attaches authoritative scope and lineage, assigns deterministic identity and version 1, and seals one `imperium.laboratorium-profile-candidate/v1`. The artifact has no predecessor, remains bound to the original Persona and live `ADMITTED_HELD` custody lease, reproduces the immutable mission scope and limitations, and carries the exact Conscription return destination.

Derivation stops at `PROFILE_CANDIDATE_DERIVED_VERSIONED_SEALED_PENDING_RETURN_TO_CONSCRIPTION`. The candidate is sealed but not returned, approved, installed, assembled for examination, examined, bound, deployed, or executed. Laboratorium acquires none of those authorities.

## Laboratorium Profile-candidate return — implemented

The candidate carries explicit one-use return authority derived from the accepted commission's exact return contract. Laboratorium revalidates the complete current chain and creates one immutable `imperium.laboratorium-conscription-profile-candidate-return/v1` in Conscription's inbox. The return references the exact candidate ID, Profile ID, version, candidate digest, Persona, scope, custody lease, and source lineage without creating or modifying a Profile.

The return stops at `PROFILE_CANDIDATE_RETURNED_PENDING_CONSCRIPTION_ACCEPTANCE`. Conscription has received the packet but has not accepted it and gains no authority merely from delivery. Garrison retains the Persona at `ADMITTED_HELD`.

## Conscription Profile-candidate acceptance — implemented

The occupied ordinary Recruiter revalidates the sealed return and exact candidate against the active bootstrap identity and live custody record. The resulting `imperium.conscription-profile-candidate-return-acceptance/v1` preserves the complete return, candidate, Persona, scope, lease, and source lineage by digest.

Acceptance stops at `PROFILE_CANDIDATE_ACCEPTED_PENDING_EXAMINATION_ASSEMBLY_AUTHORIZATION`. It consumes only the one exact acceptance act; Profile approval, installation, examination assembly, Senate examination, custody release, Persona substitution, Seat binding, deployment, and execution remain unauthorized. Garrison retains the Persona at `ADMITTED_HELD`.

## Examination-assembly authorization request — implemented

The occupied ordinary Recruiter issues one immutable `imperium.conscription-senate-examination-assembly-authorization-request/v1` to `senate.lord-speaker`. The exact Profile candidate and acceptance, Persona, mission scope, version-0 generic Officer substrate, custody lease, lineage, examination-only purpose, stand target, and return destination are sealed into the request.

The request stops at `EXAMINATION_ASSEMBLY_AUTHORIZATION_REQUESTED_PENDING_SENATE_INTAKE`. Senate intake remains pending; no Profile is installed, no manifestation is assembled, and no examination, custody release, deployment, or execution authority exists.

## Senate examination-assembly intake disposition — implemented

The occupied Lord Speaker validates the exact request against the accepted candidate, live `ADMITTED_HELD` custody lease, immutable scope, generic Officer version 0, complete lineage, and active Senate occupancy. One immutable `imperium.senate-conscription-examination-assembly-authorization-disposition/v1` records either acceptance or refusal with rationale; a second conflicting disposition for the same request is rejected.

Acceptance grants only `examination_profile_installation_authority` and `examination_assembly_authority` for the exact examination contract and stops at `EXAMINATION_ASSEMBLY_AUTHORIZED_PENDING_CONSCRIPTION_ASSEMBLY`. General Profile installation, Profile approval, Senate examination, custody release, Persona substitution, Seat binding, deployment, and execution remain false. Refusal grants nothing and stops at `EXAMINATION_ASSEMBLY_REFUSED_NO_AUTHORITY`.

## Examination Manifestation assembly — implemented

Conscription mechanically combines the exact custody-bound Persona, sealed Profile candidate, generic Officer substrate version 0, and Senate-approved examination contract. Both one-use examination authorities are consumed. The resulting ephemeral Manifestation contributes no new identity or authority, has no credentials or tools, forbids operational use, and is delivered to `senate.bailiff` at `senate.stand.intake`.

The route stops at `EXAMINATION_MANIFESTATION_ASSEMBLED_DELIVERED_PENDING_SENATE_STAND_INTAKE`. Garrison retains canonical custody at `ADMITTED_HELD`; Bailiff admission, Senate examination, Profile approval, operational qualification, deployment, and execution have not occurred.

## Bailiff stand admission — implemented

The occupied Bailiff validates the exact sealed delivery, Manifestation restrictions, live `ADMITTED_HELD` custody, and active proceeding-security authority. One immutable admission record secures the subject on `senate.stand` and stops at `EXAMINATION_MANIFESTATION_ADMITTED_SECURED_PENDING_SENATE_EXAMINATION_OPENING`. The Lord Speaker has not opened proceedings and Senate examination authority remains false.

For development verification, `imperium:dev:profile-elaboration-smoke` constructs an isolated synthetic state root under `var/imperium-dev/`, executes the authentic authorization, lease, commission, Alchemist elaboration, mechanical sealing, return, Recruiter acceptance, Senate-intake request, and Lord-Speaker disposition services, and preserves all resulting artifacts for inspection. It refuses non-development environments and never writes into the active `var/imperium/` state root.

## Profile-examination finding-authority opening — implemented

After all three exact jurisdictional questions have been dispatched unchanged and their answers sealed, the occupied Lord Speaker revalidates the testimony readiness record, examination case, live `ADMITTED_HELD` custody lease, active Trust, Security, and Usability occupancies, commissions, acceptances, testimony-turn digests, shared defect-attribution rubric, and common identity baseline. The Lord Speaker then consumes one finding-phase-opening authority and creates one bounded finding authority for each exact Senator and jurisdiction.

The route stops at `PROFILE_EXAMINATION_FINDING_AUTHORITIES_OPENED_PENDING_SENATOR_FINDINGS`. No Senator finding has been authored, and deliberation, Senate disposition, Imperator Profile approval, operational installation, Seat binding, deployment, and execution remain closed.

## Independent Profile-examination Senator findings — implemented

Each occupied Trust, Security, and Usability Senator independently consumes only its own exact jurisdiction-bound finding authority. Its cognition surface receives only its own sealed question-and-answer testimony turn, while runtime machinery revalidates the opening, current occupancy, commission, acceptance, testimony digest, Manifestation, Profile candidate, Persona identity, custody lease, shared rubric, lineage, and Conscription return destination. Each finding seals its disposition, defect attribution, evidence reference, rationale, severity, limitations, and uncertainty without exposing either of the other findings.

After all three findings exist, Senate seals `PROFILE_EXAMINATION_SENATOR_FINDINGS_SEALED_PENDING_DELIBERATION_OPENING`. The findings have not been compared, reconciled, voted, aggregated, or converted into a Senate disposition; deliberation, Imperator Profile approval, operational installation, Seat binding, deployment, and execution remain closed.

## Profile-examination deliberation opening — implemented

The occupied Lord Speaker revalidates the exact finding-readiness seal, all three independently sealed jurisdictional findings, their shared finding-authority opening, the examination case, live `ADMITTED_HELD` custody, and the complete Manifestation, Profile candidate, Persona identity, rubric, lineage, and return baseline. The three findings are admitted unchanged into one sealed deliberation-opening record; disagreement, limitations, uncertainty, severity, attribution, rationale, and evidence references remain independently preserved.

The route stops at `PROFILE_EXAMINATION_DELIBERATION_OPENED_PENDING_RECONCILIATION`. Deliberation and bounded reconciliation authority are open, but voting, aggregation, reconciliation itself, Senate disposition, Imperator Profile approval, operational installation, Seat binding, deployment, and execution remain closed.

## Profile-examination finding reconciliation — implemented

The occupied Lord Speaker consumes only the exact deliberation opening and its three admitted sealed findings. A dedicated tool-less cognition surface explains agreement, disagreement, defect attribution, severity, limitations, and uncertainty while mechanical validation requires all three exact finding references and rejects modified, omitted, nested, or disposition-bearing output.

Senate seals `PROFILE_EXAMINATION_FINDINGS_RECONCILED_PENDING_DISPOSITION_AUTHORITY_OPENING`. The three findings remain unchanged; voting and aggregation are prohibited, reconciliation authority is consumed, and Senate disposition, Imperator Profile approval, operational installation, Seat binding, deployment, and execution remain closed.

## Profile-examination disposition-authority opening — implemented

The occupied Lord Speaker mechanically revalidates the exact sealed reconciliation, all three unchanged on-disk findings, live `ADMITTED_HELD` custody, exact identity baseline, and current Lord Speaker occupancy. One disposition-phase-opening authority is consumed and exactly one Senate disposition authority becomes exercisable.

The route stops at `PROFILE_EXAMINATION_DISPOSITION_AUTHORITY_OPENED_PENDING_LORD_SPEAKER_DISPOSITION`. No disposition has been authored; reconciliation authority, voting, and aggregation are closed, and Imperator Profile approval, operational installation, Seat binding, deployment, and execution remain unauthorized.

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
