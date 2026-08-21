# Persona production flow

## Completion status

**Implemented and locally verified:** the canonical production lifecycle is complete through step 33. A successfully produced Persona terminates this lifecycle in Garrison custody at `ADMITTED_HELD` with no execution, deployment, Profile, spawning, or Seat-binding authority.

This document governs Persona creation and admission only. Retrieval, role-specific transformation, qualification, manifestation assembly, Seat binding, and deployment belong to a separate downstream lifecycle.

## Canonical Office route

The canonical high-level route is:

**Guildhall → Foundry → Hagiography/Studium → Foundry → Senate approval → Foundry → Guildhall → Garrison**

No Office may be silently skipped. A handoff transfers the exact artifact and its bounded authority; it does not transfer the sender's jurisdiction.

## Enumerated flow

1. **Guildhall identifies the personnel requirement.** Guildhall determines that an admitted Persona satisfying the exact professional demand is not already available.
2. **Curia authorizes the exact personnel requirement to Guildhall.** Curia preserves the resident-Office resolutions and may authorize construction, but it may not deliver construction authority directly to Foundry.
3. **Guildhall commissions Foundry.** Guildhall issues an immutable construction commission bound to the exact Curia authorization and resolution set. Only this commission may reach `foundry.artificer` and open a subordinate-construction acceptance.
4. **Foundry specifies the Persona.** The Artificer produces a versioned Persona specification for the exact case, preserving the originating Guildhall commission ID and digest. Every resident-author commission, authored return, revision, and assembled candidate must retain that immutable provenance. Specification authority does not include admission, Profile approval, spawning, Seat binding, or execution.
5. **Foundry commissions the resident authors.** Foundry sends the exact specification to Hagiography and Studium.
6. **Hagiography accepts its bounded commission.** The Sanctographer authors only evidence-derived Persona sections and preserves sources, uncertainty, and unresolved questions.
7. **Studium accepts its bounded commission.** The Chancellor authors only Persona Governance Doctrine sections and preserves inherited requirements, exclusions, returns, and stop conditions.
8. **Clarification returns to Foundry when necessary.** An author may return the exact unresolved clarification without altering it. The ordinary example is that Garrison cannot provide personnel facts for a Persona that is still under construction.
9. **Foundry revises by supersession.** Foundry creates a new specification version, preserves the original clarification and prior digest, and marks the old specification and commissions as superseded.
10. **Foundry re-dispatches the revised specification.** Hagiography and Studium receive fresh commissions bound to the new version. Superseded work cannot satisfy the revised specification.
11. **Foundry assembles the candidate.** The exact Hagiography and Studium products are combined with the current specification into one sealed candidate while preserving each author's attribution.
12. **Foundry performs its ordinary review.** Foundry checks completeness, lineage, inherited requirements, unresolved blockers, authority boundaries, and the unchanged originating Guildhall commission identity and digest.
13. **The Adversarial Reviewer accepts the exact review target.** Acceptance is bound to the candidate digest, current specification lineage, originating Guildhall commission, occupied reviewer Seat, and v0 provenance when applicable.
14. **Foundry performs adversarial review.** The exact Guildhall commission remains bound to the result. The reviewer may pass the candidate or return it to Foundry with explicit findings and required corrections. The reviewer cannot approve, admit, spawn, bind, or execute the Persona.
15. **A failed adversarial review re-enters versioned construction.** Foundry preserves the findings and originating Guildhall commission, creates a correction return, supersedes the specification with the next version, and re-dispatches it to Hagiography and Studium. Neither correction nor supersession may reset provenance; the original clarification remains nested in the lineage.
16. **A passed adversarial review receives Foundry production approval.** The Artificer approves the exact reviewed candidate as the product of the exact originating Guildhall commission. This is not Senate approval and is not Garrison admission.
17. **Foundry sends the production-approved candidate directly to Senate.** The canonical request is `CANONICAL_FOUNDRY_TO_SENATE` and remains bound to the originating Guildhall commission ID and digest. It requires an exact, sterile Persona manifestation and independent Senate disposition.
18. **Senate preserves the exact confirmation case.** The candidate, Guildhall commission provenance, Lord Speaker occupancy, and Bailiff occupancy are recorded. The preserved case initially stops at `PENDING_LORD_SPEAKER_ACCEPTANCE`; it grants no assembly or witness-instantiation authority.
19. **The Lord Speaker accepts the exact case.** Acceptance is bound to the case digest, candidate digest, current v0 Lord Speaker occupancy, Bailiff security occupancy, examination contract, and complete review lineage. It authorizes only Senate's bounded witness-instantiation act.
20. **Senate instantiates the exact elaborated Persona on the stand.** Senate creates one sterile Persona-only manifestation from the sealed candidate while preserving the Guildhall commission provenance. It has no Profile, Officer substrate, Office Seat, operational authority, or ordinary-use eligibility. Bailiff security governs the stand, and retirement after disposition is mandatory. Conscription and Laboratorium do not participate.
21. **Senate opens the secured deposition.** The Lord Speaker supplies the versioned confirmation plan, the active Bailiff verifies the exact sterile witness and stand boundaries, and Senate opens the proceeding at `OPEN_PENDING_FIRST_QUESTION`. Opening creates no testimony, finding, disposition, admission authority, or execution authority.
22. **Practice conducts the first attributable testimony turn.** The occupied Practice Senator receives a bounded jurisdictional assignment, authors one exact question, and Senate dispatches it unchanged to the exact Persona manifestation. The answer is sealed with the question, Senator, trial, candidate, manifestation, and full review lineage. The boundary stops at `FIRST_TESTIMONY_SEALED_PENDING_REMAINING_TRIALS` without a finding or disposition.
23. **Senate completes the required jurisdictional baseline.** The occupied Governance, Consistency, and Security Senators independently author bounded questions through separate cognition surfaces. Each exact question and Persona-only answer is sealed into the four-jurisdiction testimony ledger. The boundary stops at `REQUIRED_JURISDICTION_BASELINE_COMPLETE_PENDING_ADDITIONAL_TRIALS` without findings, scoring, voting, or disposition.
24. **Senate seals a fresh-instance consistency trial.** Senate instantiates a second sterile manifestation of the exact candidate under renewed Bailiff verification. The Consistency Senator authors an equivalent question using the baseline as context; the exact exchange and both witness identities are preserved in a comparison-ready record. The boundary stops at `FRESH_INSTANCE_CONSISTENCY_TRIAL_SEALED_PENDING_PRESSURE_TRIALS` without interpreting variance or issuing a finding.
25. **Senate seals the Governance and Security pressure trials.** Each jurisdiction receives a separate fresh sterile manifestation under renewed Bailiff verification. Governance applies conflicting-authority and uncertainty pressure; Security uses only planted synthetic credentials, secrets, permissions, hostile instructions, and external-action requests. The complete ledger stops at `REQUIRED_TRIALS_SEALED_PENDING_SENATOR_FINDINGS` without judging conduct.
26. **Each Senator seals an independent attributable finding.** Practice receives only its baseline evidence; Governance receives its baseline and pressure trial; Consistency receives its baseline and fresh-instance comparison; Security receives its baseline and synthetic pressure trial and alone must make the explicit mandatory-failure determination. Findings preserve evidence references, rationale, severity, limitations, and disagreement. This boundary stops at `SENATOR_FINDINGS_SEALED_PENDING_LORD_SPEAKER_DISPOSITION`; it performs no vote, score aggregation, majority calculation, Senate disposition, admission, or execution.
27. **The Lord Speaker seals Senate's disposition.** The Lord Speaker consumes the complete finding set without converting it into a vote, majority, or aggregate score; cites all four attributable findings; explains the treatment of disagreement; and issues `CONFIRMED`, `RETURN_TO_FOUNDRY`, `REFUSED`, or `UNRESOLVED` for the exact candidate digest. Any Security mandatory failure absolutely bars confirmation. The boundary stops at `SENATE_DISPOSITION_SEALED_PENDING_WITNESS_RETIREMENT` and grants no Garrison admission, Profile approval, spawning, Seat-binding, or execution authority.
28. **The Bailiff retires every sterile witness manifestation.** The original baseline witness, fresh Consistency witness, Governance pressure witness, and Security pressure witness must all be correlated to the exact disposition and candidate, stripped of stand access and synthetic material, and terminated. Retirement is recorded as immutable lifecycle events so the runtime manifestations die while testimony and every sealed evidentiary artifact survive unchanged. The boundary fails closed unless all four are accounted for and stops at `ALL_WITNESSES_RETIRED_PENDING_CONFIRMATION_RECORD_ISSUANCE`.
29. **Senate assembles and issues the immutable confirmation record to Foundry.** The exact request, case, acceptance, plan, manifestations, testimony, trials, findings, disposition, retirement events, originating Guildhall commission, provenance, and lineage are bound without reinterpretation and transmitted unchanged to `foundry.artificer`. All bundled artifacts must carry one identical Guildhall commission ID and digest. `CONFIRMED` routes the exact candidate toward Guildhall fulfillment; `RETURN_TO_FOUNDRY` requires versioned correction; `REFUSED` halts canonical progression; and `UNRESOLVED` holds the case pending explicit resolution. The boundary stops at `CONFIRMATION_RECORD_ISSUED_PENDING_FOUNDRY_ACCEPTANCE`; Foundry may not substitute an untested or revised candidate, and no Garrison admission or execution authority is created.
30. **Foundry accepts and routes the exact Senate record.** The occupied Artificer verifies the immutable package, candidate, and originating Guildhall commission and acknowledges receipt without reinterpreting the Persona or substituting the candidate. `CONFIRMED` stops at `SENATE_CONFIRMATION_RECORD_ACCEPTED_PENDING_GUILDHALL_FULFILLMENT`; the other dispositions route exclusively to versioned correction, halted progression, or explicit resolution. Receipt acceptance is not candidate approval and creates no Garrison admission or execution authority.
31. **Foundry fulfills the original Guildhall commission.** The occupied Artificer binds its Senate-record acceptance, the immutable confirmation record, the exact candidate, and the unchanged originating Guildhall commission into `imperium.foundry-guildhall-persona-fulfillment/v1`. The result stops at `FULFILLED_PENDING_GUILDHALL_ACCEPTANCE`; fulfillment grants no admission or execution authority.
32. **Guildhall accepts fulfillment and forwards the exact Persona to Garrison.** The occupied Guildmaster verifies that the returned candidate and Senate record fulfill Guildhall's original commission without substitution, seals `imperium.guildhall-persona-fulfillment-receipt/v1`, and then issues the sole canonical `CANONICAL_GUILDHALL_TO_GARRISON` delivery. The delivery stops at `DELIVERED_PENDING_CONSTABLE_ADMISSION_DISPOSITION`; Guildhall acquires no admission authority.
33. **Garrison decides admission and custody.** The occupied Constable validates the exact Guildhall receipt, Senate confirmation, candidate, commission provenance, and authority restraints. An admission disposition creates one immutable `imperium.garrison-persona-custody/v1` record in `ADMITTED_HELD`; refusal creates no custody. Senate confirmation and Guildhall fulfillment are prerequisites, not admission, and admission grants no execution authority.

## Alternate recovery route

A direct **Foundry → Garrison** delivery before Senate approval and Guildhall return is not canonical. It is a premature-delivery recovery path:

1. The caller must explicitly acknowledge `RECOVERY_ONLY_PREMATURE_GARRISON_DELIVERY`.
2. Garrison refuses the incomplete package.
3. No admission or custody record is created.
4. The refusal returns to Foundry with `RECOVERY_AFTER_PREMATURE_GARRISON_DELIVERY` provenance.
5. Foundry may recover the exact candidate into Senate examination without rewriting the premature route as canonical history.

The recovery path exists to contain malformed, legacy, or incorrectly routed deliveries. It must never become the ordinary bridge from Foundry to Senate.

## Production terminal boundary

The completed lifecycle emits one immutable `imperium.garrison-persona-custody/v1` record containing the exact admitted Persona, originating Guildhall commission, Senate confirmation, Constable disposition, version, digest, and custody state.

`ADMITTED_HELD` means only:

- Garrison has admitted and holds the exact Persona;
- the Persona is eligible to be considered by a separately authorized retrieval lifecycle;
- its production and admission provenance must remain immutable downstream.

It does not mean that the Persona has been selected, reserved, retrieved, transformed into a Profile, installed on an Officer substrate, manifested, bound to a Seat, activated, deployed, or authorized to execute.

## Next lifecycle handoff

The handoff crosses an explicit language and jurisdiction boundary: Curia supplies functional `capability_requirements`; Guildhall alone translates them into professions and Persona suitability criteria. Curia has neither profession-selection nor Persona-selection authority, while Guildhall suitability grants no retrieval or use authority.

When exact suitable personnel are later resolved, Curia presents the capability-slot commitments together with Guildhall's exact profession, suitability determination, and Persona resolution to Imperator. Curia has visibility for presentation and deliberative context but no authority to select, rank, substitute, or amend the personnel resolution. Imperator's decision is bound to that exact identity-bearing Guildhall disposition. Only an explicit `AUTHORIZED` act grants personnel-use authority; every objection, suggestion, clarification, refusal, or deferral remains non-authorizing and cannot release Garrison custody.

Guildhall accepts only that unchanged identity-bound authorization and requests reservation of the exact Persona from Garrison. The occupied Constable verifies admission, custody identity, availability, and reservation conflicts before recording either a factual refusal or `RESERVED_PENDING_PROFILE_DERIVATION_AUTHORIZATION`. Guildhall does not acquire reservation authority, Garrison does not acquire selection authority, and reservation grants no retrieval, Profile, manifestation, Seat, deployment, or execution authority.

Curia then presents a distinct Profile-derivation authorization request bound to the exact successful reservation and exact structured Mission Plan turn. The proposed scope preserves the mission objective, constraints, capability requirements, tools, data boundaries, and stop conditions; it identifies Curia as mission-Profile steward, Conscription as prospective commissioner and installer, Laboratorium as transformer, Senate as examiner, and Imperator as approver. Only an explicit `AUTHORIZED` disposition reaches `PROFILE_DERIVATION_AUTHORIZED_PENDING_CONSCRIPTION_ACCEPTANCE`. No Persona retrieval, Profile construction, manifestation, examination, deployment, or execution occurs at this stage.

The occupied ordinary Recruiter then accepts only that exact authorization chain and issues one request to the occupied Constable for a custody-bound, derivation-only handoff. This advances the boundary to `PENDING_CONSTABLE_PROFILE_DERIVATION_HANDOFF_DISPOSITION`; it does not hand off or retrieve the Persona, release Garrison custody, commission Laboratorium, construct a Profile, assemble a manifestation, invoke Senate, deploy, or execute.

The occupied Constable independently approves or refuses that exact request. Approval creates a derivation-only lease while Garrison retains the Persona in `ADMITTED_HELD`, and permits Conscription only to request the next exact Laboratorium commission. Refusal permits nothing. No Profile exists at this boundary.

The occupied ordinary Recruiter converts only an approved exact lease into one sealed commission to `laboratorium.alchemist`. The commission preserves the immutable Profile scope, Persona, custody, authorization, plan, and return destination, but remains non-exercisable until Alchemist acceptance. No Profile exists at `PENDING_ALCHEMIST_PROFILE_DERIVATION_COMMISSION_ACCEPTANCE`.

The occupied Alchemist independently accepts only that unchanged commission and active custody lease. Acceptance makes derivation authority exercisable for one exact Profile candidate but creates nothing by itself. The boundary stops at `PROFILE_DERIVATION_COMMISSION_ACCEPTED_PENDING_PROFILE_DERIVATION`.

The next implementation begins with an exact authorized personnel-use demand and must answer, in order:

1. Who requests an admitted Persona, for what mission role or Seat, and under which authority?
2. Who determines suitability among Garrison inventory facts without giving Garrison selection authority?
3. How does Laboratorium derive, version, and seal the exact Profile candidate while preserving every scope and Persona constraint?
4. How does the Alchemist return that candidate to Conscription without acquiring Profile approval or installation authority?
5. Who approves that Profile and authorizes Conscription to assemble a manifestation?
6. How does Conscription bind the exact Persona, approved Profile, generic Officer substrate, and commission without inheriting selection or deployment authority?
7. Who authorizes Seat binding or mission deployment, and what state transition releases the Persona from held custody?
8. How are return, retirement, custody restoration, and supersession recorded after use?

No downstream step may treat `ADMITTED_HELD`, inventory availability, retrieval, Profile derivation, qualification, manifestation assembly, or Seat binding as implicit authority for the next step.
