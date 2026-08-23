# Handoff: model-bound Profile Senate leg complete

## Repository checkpoint

- Repository: `gatomontes/imperium-symfony`
- Branch: `main`
- Merged commit: `f4fefcf8453cc23ae90d26e1ad47cbb53283e673`
- Pull request: `#241`
- GitHub Actions: `230 tests, 3,494 assertions`
- Current checkpoint: `PROFILE_EXAMINATION_DISPOSITION_SEALED_PENDING_IMPERATOR_PROFILE_APPROVAL`

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
18. Conscription assembles the examination-only Manifestation.
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

## Step 30 boundary

`ModelBoundProfileDispositionService` revalidates the exact opening, reconciliation, unchanged on-disk Trust/Security/Usability findings, disposition authority, and current Lord Speaker occupancy. Its cognition surface may return only `APPROVED`, `RETURN_FOR_REVISION`, `REFUSED`, or `UNRESOLVED`, citing every exact finding reference.

A sealed mandatory Security blocking condition mechanically rejects `APPROVED`; cognition cannot reason around or overwrite the veto. A valid result consumes Senate's single-use disposition authority and seals `PROFILE_EXAMINATION_DISPOSITION_SEALED_PENDING_IMPERATOR_PROFILE_APPROVAL`.

The record grants no Imperator Profile-approval authority, Profile installation or activation, Seat binding, deployment, execution, voting, aggregation, or continuing Senate disposition authority.

## Verification

- GitHub Actions run `#157` passed on PHP 8.4.24.
- Full suite: `230 tests, 3,494 assertions`.
- Dedicated tests cover a sealed `RETURN_FOR_REVISION` branch and mechanical rejection of `APPROVED` under a mandatory Security block.
- Local `git diff --check` passed; no local `composer install` was performed.

## New-chat starting point

Continue with model-bound step 31 only: **Imperator decides Profile approval**.

The transition must present the exact Profile candidate and complete Senate record. Senate `APPROVED` is necessary but is not itself Imperator approval or operational authority. Only an explicit approving Imperator act against an exact Senate `APPROVED` disposition may reach `IMPERATOR_PROFILE_APPROVED_PENDING_CONSCRIPTION_OPERATIONAL_QUALIFICATION`. Refusal, revision, clarification, alternative, and deferral must remain sealed non-authorizing branches.

Stop before Conscription installs or qualifies the Profile. Grant no Profile installation, Manifestation assembly, Seat binding, deployment, custody transfer, tool, credential, external-action, or execution authority.

## Copyable continuation prompt

```text
Continue Imperium from `main` at merged commit `f4fefcf8453cc23ae90d26e1ad47cbb53283e673`.

The explicit model-bound route is complete through step 30 at `PROFILE_EXAMINATION_DISPOSITION_SEALED_PENDING_IMPERATOR_PROFILE_APPROVAL`. PR #241 passed 230 tests and 3,494 assertions. Senate consumed its exact single-use disposition authority; every Trust, Security, and Usability finding and the exact reconciliation remain digest-bound; a mandatory Security blocking condition mechanically prohibits `APPROVED`; no Imperator approval or operational authority exists.

Read `docs/handoffs/model-bound-profile-senate-leg-complete.md` and `docs/next-lifecycle-persona-retrieval-and-deployment.md` before changing code.

Implement model-bound step 31 only: Imperator Profile approval. Preserve the distinction between Senate disposition, Imperator approval, and downstream authority. Only explicit Imperator approval of an exact Senate `APPROVED` disposition may open the bounded Conscription operational-qualification request. Preserve all non-approving branches as sealed and non-authorizing. Stop before qualification, installation, Manifestation assembly, Seat binding, deployment, custody transfer, tools, credentials, external action, or execution.
```
