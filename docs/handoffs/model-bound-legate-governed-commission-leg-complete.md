# Handoff: model-bound Legate governed-commission leg complete

## Repository checkpoint

- Repository: `gatomontes/imperium-symfony`
- Branch: `main`
- Merged commit: `3ea177a34e77e9b75e3a5cac131412c6325c2d78`
- Pull request: `#250`
- Current terminal checkpoints:
  - `CITADEL_LEGATE_COGNITION_RESULT_ACCEPTED_COMMISSION_CLOSED_NO_DOWNSTREAM_AUTHORITY`
  - `CITADEL_LEGATE_COGNITION_RESULT_REJECTED_COMMISSION_CLOSED_NO_DOWNSTREAM_AUTHORITY`

## Canonical personnel terminology

`Officer` is the umbrella and `generic-officer` remains the identity- and authority-neutral substrate.

- `LEGATE`: permanent, Office-bound Officer.
- `DELEGATE`: temporary examination-, mission-, proceeding-, or commission-bound Officer.

Classification grants no authority. The model-bound permanent Office route described here is a Legate route.

## Completed model-bound route

1. Curia states the capability demand.
2. Guildhall resolves profession and exact Persona.
3. Curia presents the identity-bearing personnel-use request.
4. Imperator decides personnel use.
5. Guildhall accepts and requests reservation.
6. Constable reserves the exact admitted Persona.
7. Curia constructs the immutable Profile scope.
8. Imperator authorizes Profile derivation.
9. Recruiter accepts and requests the custody-bound derivation handoff.
10. Constable decides the derivation lease.
11. Recruiter commissions Laboratorium.
12. Alchemist accepts the exact commission.
13. Alchemist elaborates and Laboratorium seals the Profile candidate.
14. Laboratorium returns the exact candidate to Conscription.
15. Recruiter accepts the returned candidate.
16. Recruiter requests examination-assembly authority.
17. Lord Speaker decides Senate intake.
18. Conscription assembles an examination-only Delegate.
19. Bailiff admits and secures it on `senate.stand`.
20. Lord Speaker opens the Profile examination.
21. Trust, Security, and Usability accept their commissions.
22. Lord Speaker opens testimony.
23. Each Senator independently seals one bounded question.
24. Each question is dispatched unchanged and each attributable answer is sealed.
25. Lord Speaker opens the finding phase.
26. Each Senator independently seals one finding.
27. Lord Speaker admits the findings unchanged and opens deliberation.
28. Lord Speaker reconciles the findings without voting or aggregation.
29. Lord Speaker opens one bounded disposition authority.
30. Lord Speaker consumes it and Senate seals one attributable disposition.
31. Imperator independently decides exact Profile approval.
32. Conscription installs and qualifies the approved Profile operationally.
33. Conscription assembles the operational Legate on generic Officer version 0.
34. Conscription atomically binds the exact Manifestation to its immutable Seat.
35. Imperator independently decides Legate activation authorization.
36. Conscription mechanically activates the exact bound Legate runtime.
37. An explicitly authorized occupied commissioner issues one exact governed commission.
38. The target Legate independently accepts or refuses it.
39. The original commissioner independently authorizes at most one bounded cognition turn.
40. Clavium activates one exact provider invocation under an opaque single-use credential lease.
41. The Legate consumes both one-use authorities for exactly one tool-less cognition turn.
42. Runtime machinery delivers the unchanged sealed result only to the original commissioner's exact current binding.
43. The original commissioner independently accepts or rejects the result and terminally closes the commission.

## Terminal Step 43 boundary

`CommissionerCognitionResultReviewService` consumes the exact Step 42 review-disposition authority. Only the exact current recipient binding may record `ACCEPTED` or `REJECTED`, each with a required rationale. Replay with the same record is idempotent; a conflicting disposition or rationale fails closed.

Acceptance means only that the sealed result satisfies the reviewed commission. It is not operational adoption, planning approval, authority to act, or evidence that the result is true beyond its declared contract and evidence.

Rejection closes the commission without a silent retry, revision, or additional cognition turn.

Both terminal records preserve the result unchanged and set operational adoption, follow-up commissioning, commission exercise, cognition, provider invocation, credential use, operational use, tools, external action, execution, and continuing-turn authority to false.

## Authority separations preserved

- Senate disposition is not Imperator Profile approval.
- Imperator Profile approval is not operational qualification or installation.
- Profile installation is not Manifestation assembly.
- Assembly is not Seat binding.
- Seat binding is not Legate activation.
- Activation is not a commission.
- Commission issuance is not Legate acceptance.
- Legate acceptance is not cognition authorization.
- Cognition authorization is not provider activation.
- Provider activation is not cognition.
- Cognition output is not delivery.
- Delivery is not commissioner acceptance.
- Commissioner acceptance is not operational adoption or authority to act.

## Verification status

- PRs `#248`, `#249`, and `#250` merged the bounded cognition, delivery, terminal review, and Legate/Delegate terminology work.
- Static `git diff --check`, service-wiring, identifier, schema, status, and terminology checks passed in the implementation workspace.
- PHP and vendor dependencies were unavailable in that workspace, so PHPUnit was not run there. Local or CI PHP 8.4 verification remains required.

## New-chat starting point

The model-bound Legate construction and one bounded governed-commission lifecycle are complete. There is no Step 44 inside this closed commission.

Any next implementation must begin a separately named lifecycle. Before coding, identify which of these is intended:

1. **Operational adoption:** present an accepted result to the proper governing body for a separate adoption decision, still without action authority.
2. **Fresh governed commission:** issue a new exact commission through Step 37; no authority from the closed commission may be reused.
3. **Legate lifecycle administration:** suspension, model-access expiry, Profile revision, supersession, retirement, or removal.
4. **Mission use:** begin the separate Delegate route; do not infer mission deployment or Garrison custody transfer from Legate activation.

## Copyable continuation prompt

```text
Continue Imperium from `main` at or after merged commit `3ea177a34e77e9b75e3a5cac131412c6325c2d78`.

Read `docs/handoffs/model-bound-legate-governed-commission-leg-complete.md`, `docs/officer-taxonomy.md`, and `docs/next-lifecycle-persona-retrieval-and-deployment.md` before changing code.

The explicit permanent model-bound Legate route is complete through Step 43. One exact governed commission was issued, independently accepted, separately authorized for at most one cognition turn, activated through Clavium under an opaque one-use credential lease, performed once without tools, delivered unchanged to the original commissioner, and terminally accepted or rejected. Both terminal branches close the commission at `...COMMISSION_CLOSED_NO_DOWNSTREAM_AUTHORITY`.

Preserve the taxonomy: Officer is the umbrella; Legates are permanent Office-bound Officers; Delegates are temporary Officers. Preserve all authority separations. Commissioner acceptance is not operational adoption, planning approval, external-action authority, or execution authority. No cognition, provider, credential, tool, follow-up commission, external action, execution, or continuing-turn authority survives Step 43.

Do not invent Step 44 inside the closed commission. First identify and name the next separate lifecycle leg—operational adoption, a fresh governed commission, Legate administration, or the Delegate mission route—and implement only its first bounded transition after stating the intended consumer, decision-maker, exact input record, output checkpoint, and authorities that must remain false.
```
