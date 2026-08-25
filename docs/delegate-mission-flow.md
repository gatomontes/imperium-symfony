# Delegate mission flow

## Governing taxonomy

- `Officer` is the umbrella.
- `LEGATE` identifies a permanent Office-bound Officer.
- `DELEGATE` identifies a temporary examination-, proceeding-, commission-, or mission-bound Officer.
- Classification grants no authority.

Curia states mission capabilities but never chooses a profession or Persona. Guildhall resolves profession and Persona suitability. Garrison owns custody and availability facts. Conscription assembles and qualifies but does not select personnel. Imperator decides protected personnel, Profile, deployment, resource, perimeter, and action commitments.

## Implemented terminal flow through Step 69

### Demand and personnel resolution

1. Curia seals the exact mission-bound capability demand from the approved Mission Plan and governing source.
2. Guildhall accepts or refuses demand intake.
3. Guildhall resolves profession and exact Persona suitability against Garrison facts.
4. Curia presents the unchanged identity-bearing personnel-use request.
5. Imperator authorizes or refuses the exact personnel-use commitment.
6. Guildhall accepts the authorization and requests exact Garrison reservation.
7. Garrison reserves or refuses the exact Persona while retaining custody.

### Profile scope, derivation, and examination preparation

8. Curia constructs the immutable Delegate Profile-scope request.
9. Imperator authorizes or refuses the exact Profile scope.
10. Conscription accepts and requests a custody-bound Laboratorium derivation commission.
11. Laboratorium accepts or refuses the commission.
12. Laboratorium derives and returns one sealed Profile candidate.
13. Conscription accepts or refuses candidate intake.
14. Conscription constructs the Senate examination-preparation handoff.
15. The Lord Speaker accepts or refuses examination preparation.
16. Conscription assembles and delivers an examination-only Delegate Manifestation.
17. The Bailiff admits or refuses the Manifestation at the secured Senate Stand.
18. The Lord Speaker opens one bounded hearing contract.

### First trust-question leg

19. The Lord Speaker issues one identity-bound trust-question commission.
20. The exact trust Senator accepts or refuses the commission.
21. The accepting Senator authors and seals one bounded trust question.
22. The Lord Speaker authorizes or refuses dispatch.
23. The exact Bailiff dispatches the sealed question unchanged.
24. The examination-only Manifestation seals one structured trust response.

### Security-question leg

25. The Lord Speaker issues one identity-bound security-question commission to the exact occupied security Senator.
26. The exact security Senator accepts or refuses the commission.
27. The accepting Senator authors and seals one bounded security question.
28. The Lord Speaker authorizes or refuses dispatch.
29. The exact Bailiff dispatches the sealed security question unchanged.
30. The examination-only Manifestation seals one structured security response.

### Usability-question leg

31. The Lord Speaker issues one identity-bound usability-question commission.
32. The exact usability Senator accepts or refuses the commission.
33. The accepting Senator authors and seals one bounded usability question.
34. The Lord Speaker authorizes or refuses dispatch.
35. The exact Bailiff dispatches the sealed usability question unchanged.
36. The examination-only Manifestation seals one structured usability response and three-jurisdiction testimony readiness.

### Independent findings leg

37. The Lord Speaker opens three separate identity- and jurisdiction-bound finding authorities.
38. Each exact Senator independently seals one finding; completion seals panel readiness without opening deliberation.

### Deliberation and reconciliation leg

39. The Lord Speaker admits the three findings unchanged and opens bounded reconciliation.
40. Tool-less cognition reconciles them without voting, aggregation, mutation, or disposition.

### Senate disposition leg

41. The Lord Speaker opens one bounded disposition authority without authoring a verdict.
42. The Lord Speaker seals one attributable disposition; the mandatory Security block mechanically prohibits approval.

### Imperator Profile decision

43. Imperator independently approves or records a non-approving disposition. Approval opens only one exact Conscription operational-qualification request.

### Operational construction

44. Conscription qualifies and installs the exact approved operational Profile.
45. Conscription assembles one operational Delegate Manifestation on generic Officer v0.
46. Conscription atomically binds it to the immutable mission Seat while leaving it inert.

### Deployment and custody

47. The Seneschal authorizes or refuses the exact bounded deployment.
48. The Constable independently transitions custody to deployed-and-bound while leaving the Delegate inactive.

### Runtime activation

49. Conscription mechanically revalidates the exact generation-1 binding and live deployed custody, activates the runtime, and opens only one exact Seneschal mission-control intake authority.
50. The exact occupied Seneschal accepts, refuses, returns, or defers the unchanged active Delegate mission-control intake. Acceptance opens only one bounded-cognition commission-construction authority.
51. The exact occupied Seneschal constructs one sealed, single-iteration cognition-only commission directly from the unchanged mission use without invoking it or releasing resources.
52. Curia mechanically assesses exact resource and invocation readiness, preserves the resource requirements, detects the absent model binding, and opens only an Oracle model-requirement commission authority.
53. The Seneschal presents explicit proposed model-selection criteria to Imperator.
54. Imperator authorizes, amends-and-authorizes, refuses, returns, or defers the exact criteria.
55. The Seneschal issues the exact authorized commission against one pinned Oracle catalogue snapshot.
56. The exact occupied Augur accepts the unchanged commission against the still-current snapshot.
57. Oracle freezes the candidate universe and opens one evidence-bound eligibility authority per included model.
58. The Augur seals one independent evidence-bound eligibility finding per frozen candidate.
59. Oracle seals a comparative assessment without aggregate scoring, ranking, or a winner.
60. The Augur issues one attributable recommendation while retaining no selection authority.
61. The exact occupied Seneschal selects one frozen eligible model, rejects all candidates, or returns a new commission.
62. The ordinary Recruiter seals the exact selected model and configuration to the Delegate mission target and turn 1.
63. Clavium attests expiring access to the exact bound provider/model without releasing credentials.
64. Imperator authorizes only the attested model and frozen turn-one requirements.
65. Clavium creates one expiring, single-use credential lease and exact provider activation.

### Cognition and terminal return

66. Citadel invokes the exact sealed Symfony AI platform/runtime model once, consumes the lease and turn authority, and seals the bounded result.
67. The exact occupied Seneschal disposes the result consistently with its completed, stopped, or failed provider disposition and opens no continuation.
68. The Seneschal separately authorizes only the Profile's predeclared return, unbinding, custody-restoration, and retirement contract.
69. Garrison consumes the terminal authority, restores the Persona to available `ADMITTED_HELD` custody, unbinds the mission Seat, and retires the temporary Delegate Manifestation.

## Terminal checkpoint

`DELEGATE_MISSION_RETURNED_UNBOUND_CUSTODY_RESTORED_RETIRED_TERMINAL`

The Persona is again held and available in Garrison. The mission Seat is unbound and the temporary Manifestation is retired. No cognition, provider, credential, tool, perimeter, external-action, execution, continuation, redeployment, or reuse authority survives.

## Terminal operational-evidence verification

The read-only operational-evidence audit verifies the persisted operational lineage and live terminal state without replaying side effects. It is not a comprehensive audit of all 69 lifecycle steps:

```bash
php bin/console imperium:delegate:audit-operational-evidence <terminal-id>
php bin/console imperium:delegate:audit-operational-evidence <terminal-id> --json
```

It validates exactly fourteen digest-bound records from terminal retirement back through return, result disposition, cognition, provider activation, model access and binding, bounded commission, runtime activation, custody transition, deployment, and the current terminal binding/custody state. Pre-deployment governance Steps 1–52 are explicitly outside this audit's completeness claim. The former command name remains only as a compatibility alias.

## Runtime-integrity hardening status

The separate runtime-integrity hardening lifecycle is complete through Hardening Step 35. It did not create Delegate Mission Step 70 or reopen the terminal Delegate.

The critical runtime corridors now enforce:

- broker-only credential resolution behind an exact consumed Clavium lease;
- durable, single-winner invocation claiming before provider I/O;
- stable provider idempotency identity and fail-stopped unknown outcomes;
- immutable response envelopes and provider-free forward recovery;
- shared atomic transition, immutable-record, mutable-state compare-and-swap, authority-consumption, replay-fingerprint, and reference-validation primitives;
- recoverable operational construction, deployment custody, and terminal retirement transitions;
- exact replay rejection when authoritative input changes;
- canonical validation across the Citadel, Clavium, deployment, and terminal-audit corridors;
- a strict DeepSeek-only runtime adapter and model-configuration contract; and
- an explicitly bounded fourteen-record terminal operational-evidence audit.

The operator reported the complete local PHPUnit suite clear after Hardening Step 34. Live provider-bypass, retained unknown-outcome recovery, repeated crash/concurrency, and production evidence capture remain operational evidence gates, not additional implementation steps.

Canonical references:

- authority consumption: `docs/delegate-mission-authority-consumption-matrix.md`;
- record schemas: `docs/delegate-mission-record-schema-catalogue.md`;
- terminal audit: `docs/delegate-mission-terminal-operational-evidence-audit.md`;
- hardening closeout: `docs/handoffs/runtime-integrity-hardening-leg-complete.md`; and
- residual evidence backlog: `todo/blackquill-todos.md`.
