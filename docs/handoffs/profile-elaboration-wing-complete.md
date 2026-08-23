# Handoff: Profile elaboration and bounded operational lifecycle complete

## Repository checkpoint

- Repository: `gatomontes/imperium-symfony`
- Branch: `main`
- Merged commit: `1a1ca6c5eb207cd8f0891384fea34e32c48a9c77`
- Pull request: `#217`
- GitHub Actions: `202 tests, 2,925 assertions`
- Terminal checkpoint: `OPERATIONAL_MANIFESTATION_RETURNED_RETIRED_CUSTODY_RESTORED`

## Completed route

The complete governed route is implemented:

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
13. Alchemist elaborates and Laboratorium seals the versioned Profile candidate.
14. Laboratorium returns the candidate to Conscription.
15. Recruiter accepts the exact returned candidate.
16. Recruiter requests examination-only assembly authority.
17. Lord Speaker decides Senate intake.
18. Conscription assembles the examination-only Manifestation.
19. Bailiff admits and secures it on `senate.stand`.
20. Lord Speaker opens the Profile examination.
21. Trust, Security, and Usability accept their commissions.
22. Lord Speaker opens testimony.
23. Each Senator independently seals one jurisdiction-bound question.
24. Each exact question is dispatched and each attributable answer is sealed.
25. Lord Speaker opens the finding phase.
26. Each Senator independently seals one finding.
27. Lord Speaker opens deliberation.
28. Lord Speaker reconciles agreement and disagreement without voting or aggregation.
29. Lord Speaker opens bounded disposition authority.
30. Senate seals one attributable disposition.
31. Imperator decides Profile approval.
32. Conscription installs and qualifies the approved operational Profile.
33. Conscription assembles the operational Manifestation.
34. Conscription binds it to the exact derived mission Seat.
35. Seneschal authorizes deployment; Constable transfers bounded operational custody.
36. Seneschal authorizes one exact input; the Manifestation consumes one internal cognition iteration.
37. Seneschal authorizes return; Constable returns and retires the Manifestation, ends the Seat binding's live effect, restores custody, and seals the terminal transcript.

The detailed normative flow remains in `docs/next-lifecycle-persona-retrieval-and-deployment.md`. The consolidated production and downstream narrative remains in `docs/persona-production-flow.md`.

## Terminal invariants

- Persona/Profile custody is restored to available `ADMITTED_HELD`.
- The mission-specific operational Manifestation is returned and retired.
- The mission Seat binding has no continuing live effect.
- The exact mission use, input, output, lineage, and terminal transition are sealed.
- No execution, continuing, redeployment, reuse, supersession, tool, credential, network, or external-action authority survives.
- A later mission must begin a new governed lifecycle; the retired Manifestation cannot be reactivated.
- Senate refusal and every non-approving Imperator disposition remain sealed non-authorizing branches.

## Live cognition resilience

The shared transient caller gives empty responses and recognizable provider timeouts three total attempts with the identical prompt and bounded linear backoff. Structural, semantic, authority, lineage, jurisdictional, and non-transient failures still fail immediately.

Reconciliation mechanically preserves jurisdiction-attributed disagreement whenever the sealed finding signatures diverge, even if live cognition returns an empty `disagreements` list. It does not invent disagreement when the signatures agree.

## Verification

Use a fresh run ID for every live smoke root:

```powershell
git switch main
git pull origin main
php vendor/bin/phpunit tests/Imperium/Runtime/ProfileElaborationSmokeServiceTest.php
php bin/console imperium:dev:profile-elaboration-smoke --run-id=operational-lifecycle-complete-001 --json
```

If that run ID already exists, increment it. `DEV01_SMOKE_ROOT_ALREADY_EXISTS` means the ID was reused, not that the governed route failed.

## New-chat starting point

The Profile-elaboration-to-bounded-deployment wing is finished. Do not reopen, extend, or weaken it by implication.

The next chat should first choose the next independent architectural wing. Plausible candidates already identified elsewhere include:

- Oracle/Augur model research, cataloging, and criteria-bound model selection;
- model assignment to permanent Citadel Officers versus mission-specific Manifestations;
- Risk Seats and their distinct jurisdictions;
- VPS/runtime load measurement before choosing more expensive Officer models;
- the next mission lifecycle built on the now-proven personnel route.

No candidate is implicitly authorized merely because this wing is complete.

## Copyable continuation prompt

```text
Continue Imperium from `main` at `1a1ca6c5`.

The complete Profile-elaboration-to-bounded-operational lifecycle is implemented and proven through `OPERATIONAL_MANIFESTATION_RETURNED_RETIRED_CUSTODY_RESTORED`. GitHub Actions passed 202 tests and 2,925 assertions. The exact operational Manifestation was returned, retired, and unbound; custody is restored to available `ADMITTED_HELD`; the terminal transcript and lineage are sealed; no execution, redeployment, reuse, supersession, tool, credential, network, external-action, or continuing authority survives.

Read `docs/handoffs/profile-elaboration-wing-complete.md`, `docs/next-lifecycle-persona-retrieval-and-deployment.md`, and `docs/persona-production-flow.md` before proposing work.

Treat the completed wing as a closed invariant. First identify and bound the next independent architectural wing; do not implement until its authority boundary and strict stopping checkpoint are explicit.
```
