# Persona production flow

## Canonical Office route

The canonical high-level route is:

**Guildhall → Foundry → Hagiography/Studium → Foundry → Senate approval → Foundry → Guildhall → Garrison**

No Office may be silently skipped. A handoff transfers the exact artifact and its bounded authority; it does not transfer the sender's jurisdiction.

## Enumerated flow

1. **Guildhall identifies the personnel requirement.** Guildhall determines that an admitted Persona satisfying the exact professional demand is not already available.
2. **Guildhall commissions Foundry.** The exact demand, requirements, source resolution, and provenance enter a Foundry subordinate-construction case.
3. **Foundry specifies the Persona.** The Artificer produces a versioned Persona specification for the exact case. Specification authority does not include admission, Profile approval, spawning, Seat binding, or execution.
4. **Foundry commissions the resident authors.** Foundry sends the exact specification to Hagiography and Studium.
5. **Hagiography accepts its bounded commission.** The Sanctographer authors only evidence-derived Persona sections and preserves sources, uncertainty, and unresolved questions.
6. **Studium accepts its bounded commission.** The Chancellor authors only Persona Governance Doctrine sections and preserves inherited requirements, exclusions, returns, and stop conditions.
7. **Clarification returns to Foundry when necessary.** An author may return the exact unresolved clarification without altering it. The ordinary example is that Garrison cannot provide personnel facts for a Persona that is still under construction.
8. **Foundry revises by supersession.** Foundry creates a new specification version, preserves the original clarification and prior digest, and marks the old specification and commissions as superseded.
9. **Foundry re-dispatches the revised specification.** Hagiography and Studium receive fresh commissions bound to the new version. Superseded work cannot satisfy the revised specification.
10. **Foundry assembles the candidate.** The exact Hagiography and Studium products are combined with the current specification into one sealed candidate while preserving each author's attribution.
11. **Foundry performs its ordinary review.** Foundry checks completeness, lineage, inherited requirements, unresolved blockers, and authority boundaries.
12. **The Adversarial Reviewer accepts the exact review target.** Acceptance is bound to the candidate digest, current specification lineage, occupied reviewer Seat, and v0 provenance when applicable.
13. **Foundry performs adversarial review.** The reviewer may pass the candidate or return it to Foundry with explicit findings and required corrections. The reviewer cannot approve, admit, spawn, bind, or execute the Persona.
14. **A failed adversarial review re-enters versioned construction.** Foundry preserves the findings, creates a correction return, supersedes the specification with the next version, and re-dispatches it to Hagiography and Studium. The original clarification remains nested in the lineage.
15. **A passed adversarial review receives Foundry production approval.** The Artificer approves the exact reviewed candidate as Foundry's production output. This is not Senate approval and is not Garrison admission.
16. **Foundry sends the production-approved candidate directly to Senate.** The canonical request is `CANONICAL_FOUNDRY_TO_SENATE`. It requires an exact, sterile, `examination_only` manifestation and independent Senate disposition.
17. **Senate preserves the exact confirmation case.** The Lord Speaker and Bailiff occupancies are recorded. At the presently implemented boundary, the case stops at `PENDING_LORD_SPEAKER_ACCEPTANCE`; no assembly or witness-instantiation authority has yet been exercised.
18. **Lord Speaker acceptance and examination assembly follow.** After explicit acceptance, Senate may issue a bounded request for the exact examination-only manifestation. This internal subflow does not change the high-level Office route.
19. **Senate examines and approves or returns the Persona.** Approval must identify the exact candidate and tested manifestation. Failure returns to the competent Foundry correction boundary with complete lineage.
20. **Senate returns the disposition to Foundry.** Foundry receives the exact Senate-approved artifact and may not substitute an untested or revised candidate.
21. **Foundry fulfills the original Guildhall commission.** The Senate-approved Persona returns to Guildhall as the product of Guildhall's original personnel demand.
22. **Guildhall forwards the accepted Persona to Garrison.** Only after Guildhall receives the exact Senate-approved result does the canonical route proceed to Garrison.
23. **Garrison decides admission and custody.** The Constable admits or rejects the exact Persona under Garrison jurisdiction. Senate approval is a prerequisite; it is not admission. Only admission creates an admitted Persona custody record.

## Alternate recovery route

A direct **Foundry → Garrison** delivery before Senate approval and Guildhall return is not canonical. It is a premature-delivery recovery path:

1. The caller must explicitly acknowledge `RECOVERY_ONLY_PREMATURE_GARRISON_DELIVERY`.
2. Garrison refuses the incomplete package.
3. No admission or custody record is created.
4. The refusal returns to Foundry with `RECOVERY_AFTER_PREMATURE_GARRISON_DELIVERY` provenance.
5. Foundry may recover the exact candidate into Senate examination without rewriting the premature route as canonical history.

The recovery path exists to contain malformed, legacy, or incorrectly routed deliveries. It must never become the ordinary bridge from Foundry to Senate.
